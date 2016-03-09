"""Hello, pep.py here, ex-owner of ripple and prime minister of Ripwot."""
# TODO: Remove useless imports
# TODO: Docs
import struct
import gzip
import string
import logging
import sys
import uuid
import pymysql
import os
import time
import flask

# Tornado server
from tornado.wsgi import WSGIContainer
from tornado.httpserver import HTTPServer
from tornado.ioloop import IOLoop

# pep.py files
import bcolors
import packetIDs
import serverPackets
import clientPackets
import config
import dataTypes
import userHelper
import osuToken
import tokenList
import exceptions
import gameModes
import locationHelper
import glob
import fokabot
import countryHelper
import banchoConfig

# pep.py helpers
import packetHelper
import consoleHelper
import databaseHelper
import passwordHelper
import responseHelper
import generalFunctions
import systemHelper

import threading

# Create flask instance
app = flask.Flask(__name__)

# Get flask logger
flaskLogger = logging.getLogger("werkzeug")

# Ci trigger
@app.route("/ci-trigger")
def ciTrigger():
	# Ci restart trigger

	# Get ket from GET
	key = flask.request.args.get('k')

	# Get request ip
	requestIP = flask.request.headers.get('X-Real-IP')
	if (requestIP == None):
		requestIP = flask.request.remote_addr

	# Check key
	if (key is None or key != glob.conf.config["ci"]["key"]):
		consoleHelper.printColored("[!] Invalid ci trigger from {}".format(requestIP), bcolors.RED)
		return "Invalid key"

	# Ci event triggered, schedule server shutdown
	consoleHelper.printColored("[!] Ci event triggered from {}".format(requestIP), bcolors.PINK)
	systemHelper.scheduleShutdown(5, False, "A new Bancho update is available and the server will be restarted in 5 seconds. Thank you for your patience.")
	return "Ci event triggered"

# Main bancho server
@app.route("/", methods=['GET', 'POST'])
def banchoServer():
	if (flask.request.method == 'POST'):
		# Client's token
		requestToken = flask.request.headers.get('osu-token')

		# Client's request data
		# We remove the first two and last three characters because they are
		# some escape stuff that we don't need
		requestData = flask.request.data

		# Client's IP
		requestIP = flask.request.headers.get('X-Real-IP')
		if (requestIP == None):
			requestIP = flask.request.remote_addr

		# Server's response data
		responseData = bytes()

		# Server's response token string
		responseTokenString = "ayy";

		if (requestToken == None):
			# We don't have a token, this is the first packet aka login
			print("> Accepting connection from "+requestIP+"...")

			# Split POST body so we can get username/password/hardware data
			loginData = str(requestData)[2:-3].split("\\n")

			# Process login
			print("> Processing login request for "+loginData[0]+"...")
			try:
				# If true, print error to console
				err = False

				# Try to get the ID from username
				userID = userHelper.getUserID(str(loginData[0]))

				if (userID == False):
					# Invalid username
					raise exceptions.loginFailedException()
				if (userHelper.checkLogin(userID, loginData[1]) == False):
					# Invalid password
					raise exceptions.loginFailedException()

				# Make sure we are not banned
				userAllowed = userHelper.getUserAllowed(userID)
				if (userAllowed == 0):
					# Banned
					raise exceptions.loginBannedException()

				# No login errors!
				# Delete old tokens for that user and generate a new one
				glob.tokens.deleteOldTokens(userID)
				responseToken = glob.tokens.addToken(userID)
				responseTokenString = responseToken.token

				# Print logged in message
				consoleHelper.printColored("> "+loginData[0]+" logged in ("+responseToken.token+")", bcolors.GREEN)

				# Get silence end
				userSilenceEnd = max(0, userHelper.getUserSilenceEnd(userID)-int(time.time()))

				# Get supporter/GMT
				userRank = userHelper.getUserRank(userID)
				userGMT = False
				userSupporter = True
				if (userRank >= 3):
					userGMT = True

				# Maintenance check
				if (glob.banchoConf.config["banchoMaintenance"] == True):
					if (userGMT == False):
						# We are not mod/admin, delete token, send notification and logout
						glob.tokens.deleteToken(responseTokenString)
						raise exceptions.banchoMaintenanceException()
					else:
						# We are mod/admin, send warning notification and continue
						responseToken.enqueue(serverPackets.notification("Bancho is in maintenance mode. Only mods/admins have full access to the server.\nType !system maintenance off in chat to turn off maintenance mode."))

				# Send all needed login packets
				responseToken.enqueue(serverPackets.silenceEndTime(userSilenceEnd))
				responseToken.enqueue(serverPackets.userID(userID))
				responseToken.enqueue(serverPackets.protocolVersion())
				responseToken.enqueue(serverPackets.userSupporterGMT(userSupporter, userGMT))
				responseToken.enqueue(serverPackets.userPanel(userID))
				responseToken.enqueue(serverPackets.userStats(userID))

				# Channel info end (before starting!?! wtf bancho?)
				responseToken.enqueue(serverPackets.channelInfoEnd())

				# TODO: Configurable default channels
				# Default opened channels
				glob.channels.channels["#osu"].userJoin(userID)
				responseToken.joinChannel("#osu")
				glob.channels.channels["#announce"].userJoin(userID)
				responseToken.joinChannel("#announce")

				responseToken.enqueue(serverPackets.channelJoinSuccess(userID, "#osu"))
				responseToken.enqueue(serverPackets.channelJoinSuccess(userID, "#announce"))

				# Output channels info
				for key, value in glob.channels.channels.items():
					responseToken.enqueue(serverPackets.channelInfo(key))

				responseToken.enqueue(serverPackets.friendList(userID))

				# Send main menu icon and login notification if needed
				if (glob.banchoConf.config["menuIcon"] != ""):
					responseToken.enqueue(serverPackets.mainMenuIcon(glob.banchoConf.config["menuIcon"]))

				if (glob.banchoConf.config["loginNotification"] != ""):
					responseToken.enqueue(serverPackets.notification(glob.banchoConf.config["loginNotification"]))

				# Get everyone else userpanel
				# TODO: Better online users handling
				for key, value in glob.tokens.tokens.items():
					responseToken.enqueue(serverPackets.userPanel(value.userID))
					responseToken.enqueue(serverPackets.userStats(value.userID))

				# Send online users IDs array
				responseToken.enqueue(serverPackets.onlineUsers())

				# Send to everyone our userpanel and userStats (so they now we have logged in)
				glob.tokens.enqueueAll(serverPackets.userPanel(userID))
				glob.tokens.enqueueAll(serverPackets.userStats(userID))

				# Set position and country
				responseToken.setLocation(locationHelper.getLocation(requestIP))
				responseToken.setCountry(countryHelper.getCountryID(locationHelper.getCountry(requestIP)))

				# Set reponse data to right value and reset our queue
				responseData = responseToken.queue
				responseToken.resetQueue()
			except exceptions.loginFailedException:
				# Login failed error packet
				# (we don't use enqueue because we don't have a token since login has failed)
				err = True
				responseData += serverPackets.loginFailed()
			except exceptions.loginBannedException:
				# Login banned error packet
				err = True
				responseData += serverPackets.loginBanned()
			except exceptions.banchoMaintenanceException:
				# Bancho is in maintenance mode
				responseData += serverPackets.notification("Our bancho server is in maintenance mode. Please try to login again later.")
				responseData += serverPackets.loginError()
			finally:
				# Print login failed message to console if needed
				if (err == True):
					consoleHelper.printColored("> "+loginData[0]+"'s login failed", bcolors.YELLOW)
		else:
			try:
				# This is not the first packet, send response based on client's request
				# Packet start position, used to read stacked packets
				pos = 0

				# Make sure the token exists
				if (requestToken not in glob.tokens.tokens):
					raise exceptions.tokenNotFoundException()

				# Token exists, get its object
				userToken = glob.tokens.tokens[requestToken]

				# Get userID and username from token
				userID = userToken.userID
				username = userToken.username
				userRank = userToken.rank

				# Keep reading packets until everything has been read
				while pos < len(requestData):
					# Get packet from stack starting from new packet
					leftData = requestData[pos:]

					# Get packet ID, data length and data
					packetID = packetHelper.readPacketID(leftData)
					dataLength = packetHelper.readPacketLength(leftData)
					packetData = requestData[pos:(pos+dataLength+7)]

					# Console output if needed
					if (serverOutputPackets == True and packetID != 4):
						consoleHelper.printColored("Incoming packet ("+requestToken+")("+username+"):", bcolors.GREEN)
						consoleHelper.printColored("Packet code: "+str(packetID)+"\nPacket length: "+str(dataLength)+"\nSingle packet data: "+str(packetData)+"\n", bcolors.YELLOW)

					# Packet switch
					if (packetID == packetIDs.client_pong):
						# Ping packet, nothing to do
						# New packets are automatically taken from the queue
						pass
					elif (packetID == packetIDs.client_sendPublicMessage):
						try:
							# Public chat packet
							packetData = clientPackets.sendPublicMessage(packetData)

							# Receivers
							who = []

							# Check #spectator
							if (packetData["to"] != "#spectator"):
								# Standard channel
								# Make sure the channel exists
								if (packetData["to"] not in glob.channels.channels):
									raise exceptions.channelUnknownException

								# Make sure the channel is not in moderated mode
								if (glob.channels.channels[packetData["to"]].moderated == True and userRank < 2):
									raise exceptions.channelModeratedException

								# Make sure we have write permissions
								if (glob.channels.channels[packetData["to"]].publicWrite == False and userRank < 2):
									raise exceptions.channelNoPermissionsException

								# Send this packet to everyone in that channel except us
								who = glob.channels.channels[packetData["to"]].getConnectedUsers()[:]
								if userID in who:
									who.remove(userID)
							else:
								# Spectator channel
								# Send this packet to every spectator and host
								if (userToken.spectating == 0):
									# We have sent to send a message to our #spectator channel
									targetToken = userToken
									who = targetToken.spectators[:]
									# No need to remove us because we are the host so we are not in spectators list
								else:
									# We have sent a message to someone else's #spectator
									targetToken = glob.tokens.getTokenFromUserID(userToken.spectating)
									who = targetToken.spectators[:]

									# Remove us
									if (userID in who):
										who.remove(userID)

									# Add host
									who.append(targetToken.userID)

							# Send packet to required users
							glob.tokens.multipleEnqueue(serverPackets.sendMessage(username, packetData["to"], packetData["message"]), who, False)

							# Fokabot command check
							fokaMessage = fokabot.fokabotResponse(username, packetData["to"], packetData["message"])
							if (fokaMessage != False):
								who.append(userID)
								glob.tokens.multipleEnqueue(serverPackets.sendMessage("FokaBot", packetData["to"], fokaMessage), who, False)
								consoleHelper.printColored("> FokaBot@"+packetData["to"]+": "+str(fokaMessage.encode("UTF-8")), bcolors.PINK)

							# Console output
							consoleHelper.printColored("> "+username+"@"+packetData["to"]+": "+str(packetData["message"].encode("UTF-8")), bcolors.PINK)
						except exceptions.channelModeratedException:
							consoleHelper.printColored("[!] "+username+" has attempted to send a message to a channel that is in moderated mode ("+packetData["to"]+")", bcolors.RED)
						except exceptions.channelUnknownException:
							consoleHelper.printColored("[!] "+username+" has attempted to send a message to an unknown channel ("+packetData["to"]+")", bcolors.RED)
						except exceptions.channelNoPermissionsException:
							consoleHelper.printColored("[!] "+username+" has attempted to send a message to channel "+packetData["to"]+", but he has no write permissions", bcolors.RED)

					elif (packetID == packetIDs.client_sendPrivateMessage):
						try:
							# Private message packet
							packetData = clientPackets.sendPrivateMessage(packetData)

							if (packetData["to"] == "FokaBot"):
								# FokaBot command check
								fokaMessage = fokabot.fokabotResponse(username, packetData["to"], packetData["message"])
								if (fokaMessage != False):
									userToken.enqueue(serverPackets.sendMessage("FokaBot", username, fokaMessage))
									consoleHelper.printColored("> FokaBot>"+packetData["to"]+": "+str(fokaMessage.encode("UTF-8")), bcolors.PINK)
							else:
								# Send packet message to target if it exists
								token = glob.tokens.getTokenFromUsername(packetData["to"])
								if (token == None):
									raise exceptions.tokenNotFoundException()
								token.enqueue(serverPackets.sendMessage(username, packetData["to"], packetData["message"]))

							# Console output
							consoleHelper.printColored("> "+username+">"+packetData["to"]+": "+packetData["message"], bcolors.PINK)
						except exceptions.tokenNotFoundException:
							# Token not found, user disconnected
							consoleHelper.printColored("[!] "+username+" has tried to send a message to "+packetData["to"]+", but its token couldn't be found", bcolors.RED)
					elif (packetID == packetIDs.client_channelJoin):
						try:
							# Channel join packet
							packetData = clientPackets.channelJoin(packetData)

							# Check spectator channel
							# If it's spectator channel, skip checks and list stuff
							if (packetData["channel"] != "#spectator"):
								# Normal channel, do check stuff
								# Make sure the channel exists
								if (packetData["channel"] not in glob.channels.channels):
									raise exceptions.channelUnknownException

								# Check channel permissions
								if ((glob.channels.channels[packetData["channel"]].publicWrite == False or glob.channels.channels[packetData["channel"]].moderated == True) and userRank < 2):
									raise exceptions.channelNoPermissionsException

								# Add our userID to users in that channel
								glob.channels.channels[packetData["channel"]].userJoin(userID)

								# Add the channel to our joined channel
								userToken.joinChannel(packetData["channel"])

							# Send channel joined
							userToken.enqueue(serverPackets.channelJoinSuccess(userID, packetData["channel"]))

							# Console output
							consoleHelper.printColored("> "+username+" has joined channel "+packetData["channel"], bcolors.GREEN)
						except exceptions.channelNoPermissionsException:
							consoleHelper.printColored("[!] "+username+" has attempted to join channel "+packetData["channel"]+", but he has no read permissions", bcolors.RED)
						except exceptions.channelUnknownException:
							consoleHelper.printColored("[!] "+username+" has attempted to join an unknown channel ("+packetData["channel"]+")", bcolors.RED)
					elif (packetID == packetIDs.client_channelPart):
						# Channel part packet
						packetData = clientPackets.channelPart(packetData)

						# Remove us from joined users and joined channels
						if packetData["channel"] in glob.channels.channels:
							userToken.partChannel(packetData["channel"])
							glob.channels.channels[packetData["channel"]].userPart(userID)

							# Console output
							consoleHelper.printColored("> "+username+" has parted channel "+packetData["channel"], bcolors.YELLOW)
					elif (packetID == packetIDs.client_changeAction):
						# Change action packet
						packetData = clientPackets.userActionChange(packetData)

						# Update our action id, text and md5
						userToken.actionID = packetData["actionID"]
						userToken.actionText = packetData["actionText"]
						userToken.actionMd5 = packetData["actionMd5"]
						userToken.actionMods = packetData["actionMods"]
						userToken.gameMode = packetData["gameMode"]

						# Enqueue our new user panel and stats to everyone
						glob.tokens.enqueueAll(serverPackets.userPanel(userID))
						glob.tokens.enqueueAll(serverPackets.userStats(userID))

						# Console output
						print("> "+username+" has changed action: "+str(userToken.actionID)+" ["+userToken.actionText+"]["+userToken.actionMd5+"]")
					elif (packetID == packetIDs.client_startSpectating):
						try:
							# Start spectating packet
							packetData = clientPackets.startSpectating(packetData)

							# Stop spectating old user if needed
							if (userToken.spectating != 0):
								oldTargetToken = glob.tokens.getTokenFromUserID(userToken.spectating)
								oldTargetToken.enqueue(serverPackets.removeSpectator(userID))
								userToken.stopSpectating()

							# Start spectating new user
							userToken.startSpectating(packetData["userID"])

							# Get host token
							targetToken = glob.tokens.getTokenFromUserID(packetData["userID"])
							if (targetToken == None):
								raise exceptions.tokenNotFoundException

							# Add us to host's spectators
							targetToken.addSpectator(userID)

							# Send spectator join packet to host
							targetToken.enqueue(serverPackets.addSpectator(userID))

							# Join #spectator channel
							userToken.enqueue(serverPackets.channelJoinSuccess(userID, "#spectator"))

							if (len(targetToken.spectators) == 1):
								# First spectator, send #spectator join to host too
								targetToken.enqueue(serverPackets.channelJoinSuccess(userID, "#spectator"))

							# Console output
							consoleHelper.printColored("> "+username+" is spectating "+userHelper.getUserUsername(packetData["userID"]), bcolors.PINK)
							consoleHelper.printColored("> {}'s spectators: {}".format(str(packetData["userID"]), str(targetToken.spectators)), bcolors.BLUE)
						except exceptions.tokenNotFoundException:
							# Stop spectating if token not found
							consoleHelper.printColored("[!] Spectator start: token not found", bcolors.RED)
							userToken.stopSpectating()
					elif (packetID == packetIDs.client_stopSpectating):
						try:
							# Stop spectating packet, has no parameters

							# Remove our userID from host's spectators
							target = userToken.spectating
							targetToken = glob.tokens.getTokenFromUserID(target)
							if (targetToken == None):
								raise exceptions.tokenNotFoundException
							targetToken.removeSpectator(userID)

							# Send the spectator left packet to host
							targetToken.enqueue(serverPackets.removeSpectator(userID))

							# Console output
							consoleHelper.printColored("> "+username+" is no longer spectating whoever he was spectating", bcolors.PINK)
							consoleHelper.printColored("> {}'s spectators: {}".format(str(target), str(targetToken.spectators)), bcolors.BLUE)
						except exceptions.tokenNotFoundException:
							consoleHelper.printColored("[!] Spectator stop: token not found", bcolors.RED)
						finally:
							# Set our spectating user to 0
							userToken.stopSpectating()
					elif (packetID == packetIDs.client_cantSpectate):
						try:
							# We don't have the beatmap, we can't spectate
							target = userToken.spectating
							targetToken = glob.tokens.getTokenFromUserID(target)

							# Send the packet to host
							targetToken.enqueue(serverPackets.noSongSpectator(userID))
						except exceptions.tokenNotFoundException:
							# Stop spectating if token not found
							consoleHelper.printColored("[!] Spectator can't spectate: token not found", bcolors.RED)
							userToken.stopSpectating()
					elif (packetID == packetIDs.client_spectateFrames):
						# Client spectate frames
						# Send spectator frames to every spectator
						consoleHelper.printColored("> {}'s spectators: {}".format(str(userID), str(userToken.spectators)), bcolors.BLUE)
						for i in userToken.spectators:
							if (i != userID):
								# Send to every spectator but us (host)
								spectatorToken = glob.tokens.getTokenFromUserID(i)
								if (spectatorToken != None):
									# Token found, send frames
									spectatorToken.enqueue(serverPackets.spectatorFrames(packetData[7:]))
								else:
									# Token not found, remove it
									userToken.removeSpectator(i)
									userToken.enqueue(serverPackets.removeSpectator(i))
					elif (packetID == packetIDs.client_friendAdd):
						# Friend add packet
						packetData = clientPackets.addRemoveFriend(packetData)
						userHelper.addFriend(userID, packetData["friendID"])

						# Console output
						print("> "+username+" has added "+str(packetData["friendID"])+" to his friends")
					elif (packetID == packetIDs.client_friendRemove):
						# Friend remove packet
						packetData = clientPackets.addRemoveFriend(packetData)
						userHelper.removeFriend(userID, packetData["friendID"])

						# Console output
						print("> "+username+" has removed "+str(packetData["friendID"])+" from his friends")
					elif (packetID == packetIDs.client_logout):
						# Logout packet, no parameters to read

						# Big client meme here. If someone logs out and logs in right after,
						# the old logout packet will still be in the queue and will be sent to
						# the server, so we accept logout packets sent at least 5 seconds after login
						if (int(time.time()-userToken.loginTime) >= 5):
							# TODO: Channel part at logout
							# Enqueue our disconnection to everyone else
							glob.tokens.enqueueAll(serverPackets.userLogout(userID))

							# Delete token
							glob.tokens.deleteToken(requestToken)

							consoleHelper.printColored("> "+username+" has been disconnected (logout)", bcolors.YELLOW)

					# Set reponse data and tokenstring to right value and reset our queue

					# Update pos so we can read the next stacked packet
					pos += dataLength+7	# add packet ID bytes, unused byte and data length bytes
				# WHILE END

				# Token queue built, send it
				# TODO: Move somewhere else
				responseTokenString = userToken.token
				responseData = userToken.queue
				userToken.resetQueue()
			except exceptions.tokenNotFoundException:
				# Token not found. Disconnect that user
				responseData = serverPackets.loginError()
				responseData += serverPackets.notification("Whoops! Something went wrong, please login again.")
				consoleHelper.printColored("[!] Received packet from unknown token ("+requestToken+").", bcolors.RED)
				consoleHelper.printColored("> "+requestToken+" has been disconnected (invalid token)", bcolors.YELLOW)

		# Send server's response to client
		# We don't use token object because we might not have a token (failed login)
		return responseHelper.generateResponse(responseTokenString, responseData)
	else:
		# Not a POST request, send html page
		# TODO: Fix this crap
		return responseHelper.HTMLResponse()



# Server start
consoleHelper.printServerStartHeader(True);

# Read config.ini
consoleHelper.printNoNl("> Loading config file... ")
glob.conf = config.config("config.ini")

if (glob.conf.default == True):
	# We have generated a default config.ini, quit server
	consoleHelper.printWarning()
	consoleHelper.printColored("[!] config.ini not found. A default one has been generated.", bcolors.YELLOW)
	consoleHelper.printColored("[!] Please edit your config.ini and run the server again.", bcolors.YELLOW)
	sys.exit()

# If we haven't generated a default config.ini, check if it's valid
if (glob.conf.checkConfig() == False):
	consoleHelper.printError()
	consoleHelper.printColored("[!] Invalid config.ini. Please configure it properly", bcolors.RED)
	consoleHelper.printColored("[!] Delete your config.ini to generate a default one", bcolors.RED)
	sys.exit()
else:
	consoleHelper.printDone()


# Connect to db
try:
	consoleHelper.printNoNl("> Connecting to MySQL db... ")
	glob.db = databaseHelper.db(glob.conf.config["db"]["host"], glob.conf.config["db"]["username"], glob.conf.config["db"]["password"], glob.conf.config["db"]["database"])
	consoleHelper.printDone()
except:
	# Exception while connecting to db
	consoleHelper.printError()
	consoleHelper.printColored("[!] Error while connection to database. Please check your config.ini and run the server again", bcolors.RED)
	raise

# Load bancho_settings
try:
	consoleHelper.printNoNl("> Loading bancho settings from DB... ")
	glob.banchoConf = banchoConfig.banchoConfig()
	consoleHelper.printDone()
except:
	consoleHelper.printError()
	consoleHelper.printColored("[!] Error while loading bancho_settings. Please make sure the table in DB has all the required rows", bcolors.RED)
	raise

# Initialize chat channels
consoleHelper.printNoNl("> Initializing chat channels... ")
glob.channels.loadChannels()
consoleHelper.printDone()

# Start fokabot
consoleHelper.printNoNl("> Connecting FokaBot... ")
fokabot.connect()
consoleHelper.printDone()

# Get server parameters from config.ini
serverName = glob.conf.config["server"]["server"]
serverHost = glob.conf.config["server"]["host"]
serverPort = int(glob.conf.config["server"]["port"])
serverOutputPackets = generalFunctions.stringToBool(glob.conf.config["server"]["outputpackets"])

# Run server sanic way
if (serverName == "tornado"):
	# Tornado server
	print("> Starting tornado...");
	webServer = HTTPServer(WSGIContainer(app))
	webServer.listen(serverPort)
	IOLoop.instance().start()
elif (serverName == "flask"):
	# Flask server
	# Get flask settings
	flaskThreaded = generalFunctions.stringToBool(glob.conf.config["flask"]["threaded"])
	flaskDebug = generalFunctions.stringToBool(glob.conf.config["flask"]["debug"])
	flaskLoggerStatus = not generalFunctions.stringToBool(glob.conf.config["flask"]["logger"])

	# Set flask debug mode and logger
	app.debug = flaskDebug
	flaskLogger.disabled = flaskLoggerStatus

	# Console output
	if (flaskDebug == False):
		print("> Starting flask...");
	else:
		print("> Starting flask in "+bcolors.YELLOW+"debug mode..."+bcolors.ENDC)

	# Run flask server
	app.run(host=serverHost, port=serverPort, threaded=flaskThreaded)
else:
	print(bcolors.RED+"[!] Unknown server. Please set the server key in config.ini to "+bcolors.ENDC+bcolors.YELLOW+"tornado"+bcolors.ENDC+bcolors.RED+" or "+bcolors.ENDC+bcolors.YELLOW+"flask"+bcolors.ENDC)
	sys.exit()
