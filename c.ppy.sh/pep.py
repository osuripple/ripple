# TODO: Remove useless imports
# TODO: Docs
import struct
import flask
import gzip
import string
import logging
import sys
import uuid
import pymysql
import os
import time

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

import packetHelper
import consoleHelper
import databaseHelper
import passwordHelper
import responseHelper

# Create flask instance
app = flask.Flask(__name__)

# Get flask logger
flaskLogger = logging.getLogger("werkzeug")

# Convert a string (True/true/1) to bool
# TODO: Move this function to another file
def stringToBool(s):
	if (s == "True" or s== "true" or s == "1" or s == 1):
		return True
	else:
		return False

def hexString(s):
	return ":".join("{:02x}".format(ord(c)) for c in s)

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

				# Get silence end
				userSilenceEnd = max(0, userHelper.getUserSilenceEnd(userID)-int(time.time()))

				# Get supporter/GMT
				userRank = userHelper.getUserRank(userID)
				userGMT = False
				userSupporter = True
				if (userRank >= 3):
					userGMT = True

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

				# TODO: Move this thing
				glob.channels.channels["#osu"].userJoin(userID)
				responseToken.joinChannel("#osu")
				glob.channels.channels["#announce"].userJoin(userID)
				responseToken.joinChannel("#announce")

				responseToken.enqueue(serverPackets.channelJoinSuccess(userID, "#osu"))
				responseToken.enqueue(serverPackets.channelJoinSuccess(userID, "#announce"))

				# Test notification
				responseToken.enqueue(serverPackets.notification("Welcome to pep.py server!"))

				# Output channels info
				for key, value in glob.channels.channels.items():
					responseToken.enqueue(serverPackets.channelInfo(key))

				# TODO: Online users IDs
				responseToken.enqueue(serverPackets.friendList(userID))
				#responseToken.enqueue(serverPackets.onlineUsers())

				# Print logged in message
				consoleHelper.printColored("> "+loginData[0]+" logged in ("+responseToken.token+")", bcolors.GREEN)

				# Set position
				responseToken.setLocation(locationHelper.getLocation(requestIP))

				# Send to everyone our userpanel and userStats (so they now we have logged in)
				glob.tokens.enqueueAll(serverPackets.userPanel(userID))
				glob.tokens.enqueueAll(serverPackets.userStats(userID))

				# Get everyone else userpanel
				# TODO: Better online users handling
				for key, value in glob.tokens.tokens.items():
					responseToken.enqueue(serverPackets.userPanel(value.userID))
					responseToken.enqueue(serverPackets.userStats(value.userID))

				# Set reponse data and tokenstring to right value and reset our queue
				responseTokenString = responseToken.token
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
					# Get packet from stack
					packetData = requestData[pos:]

					# Get packet ID and data length
					packetID = packetHelper.readPacketID(packetData)
					dataLength = packetHelper.readPacketLength(packetData)

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
								who = glob.channels.channels[packetData["to"]].getConnectedUsers().copy()
								if userID in who:
									who.remove(userID)
							else:
								print("spectmeme")
								# Spectator channel
								# Send this packet to every spectator
								if (userToken.spectating == 0):
									# We have sent to send a message to our #spectator channel
									targetToken = userToken
								else:
									# We have sent a message to someone else's #spectator
									targetToken = glob.tokens.getTokenFromUserID(userToken.spectating)

								who = targetToken.spectators.copy()
								if userID in who:
									who.remove(userID)

							# Send packet to required users
							glob.tokens.multipleEnqueue(serverPackets.sendMessage(username, packetData["to"], packetData["message"]), who, False)

							# Fokabot command check
							fokaMessage = fokabot.fokabotResponse(username, packetData["to"], packetData["message"])
							if (fokaMessage != False):
								glob.tokens.multipleEnqueue(serverPackets.sendMessage("FokaBot", packetData["to"], fokaMessage), glob.channels.channels[packetData["to"]].getConnectedUsers().copy(), False)
								consoleHelper.printColored("> FokaBot@"+packetData["to"]+": "+str(fokaMessage.encode("UTF-8")), bcolors.HEADER)

							# Console output
							consoleHelper.printColored("> "+username+"@"+packetData["to"]+": "+str(packetData["message"].encode("UTF-8")), bcolors.HEADER)
						except exceptions.channelModeratedException:
							consoleHelper.printColored("[!] "+username+" has attempted to send a message to a channel that is in moderated mode ("+packetData["to"]+")", bcolors.RED)
						except exceptions.channelUnknownException:
							consoleHelper.printColored("[!] "+username+" has attempted to send a message to an unknown channel ("+packetData["to"]+")", bcolors.RED)
						except exceptions.channelNoPermissionsException:
							consoleHelper.printColored("[!] "+username+" has attempted to send a message to channel "+packetData["to"]+", but he has no write permissions", bcolors.RED)

					elif (packetID == packetIDs.client_sendPrivateMessage):
						# Private message packet
						packetData = clientPackets.sendPrivateMessage(packetData)

						# Send packet message to target if it exists
						glob.tokens.getTokenFromUsername(packetData["to"]).enqueue(serverPackets.sendMessage(username, packetData["to"], packetData["message"]))

						# Console output
						consoleHelper.printColored("> "+username+">"+packetData["to"]+": "+packetData["message"], bcolors.HEADER)
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

						# Enqueue our new user panel and stats to everyone
						glob.tokens.enqueueAll(serverPackets.userPanel(userID))
						glob.tokens.enqueueAll(serverPackets.userStats(userID))

						# Console output
						print("> "+username+" has changed action: "+str(userToken.actionID)+" ["+userToken.actionText+"]["+userToken.actionMd5+"]")
					elif (packetID == packetIDs.client_startSpectating):
						try:
							# Start spectating packet
							packetData = clientPackets.startSpectating(packetData)

							# Set spectating user ID
							userToken.startSpectating(packetData["userID"])

							# Add new spectator to tatget's spectators
							targetToken = glob.tokens.getTokenFromUserID(packetData["userID"])
							if (targetToken == None):
								raise exceptions.tokenNotFoundException
							targetToken.addSpectator(userID)
							targetToken.enqueue(serverPackets.addSpectator(userID))

							# Console output
							consoleHelper.printColored("> "+username+" is spectating "+userHelper.getUserUsername(packetData["userID"]), bcolors.HEADER)
						except exceptions.tokenNotFoundException:
							consoleHelper.printColored("[!] Spectator start: token not found", bcolors.RED)
					elif (packetID == packetIDs.client_stopSpectating):
						try:
							# Stop spectating packet, has no parameters

							# Remove our ID spectator from tatget's spectators
							target = userToken.spectating
							targetToken = glob.tokens.getTokenFromUserID(target)
							if (targetToken == None):
								raise exceptions.tokenNotFoundException
							targetToken.removeSpectator(userID)
							targetToken.enqueue(serverPackets.removeSpectator(userID))
							# Set spectating userID to 0
							userToken.stopSpectating()

							# Console output
							consoleHelper.printColored("> "+username+" is no longer spectating whoever he was spectating", bcolors.HEADER)
						except exceptions.tokenNotFoundException:
							consoleHelper.printColored("[!] Spectator stop: token not found", bcolors.RED)
					elif (packetID == packetIDs.client_cantSpectate):
						try:
							# We don't have the beatmap, we can't spectate
							target = userToken.spectating
							targetToken = glob.tokens.getTokenFromUserID(target)
							targetToken.enqueue(serverPackets.noSongSpectator(userID))
						except exceptions.tokenNotFoundException:
							consoleHelper.printColored("[!] Spectator can't spectate: token not found", bcolors.RED)
					elif (packetID == packetIDs.client_spectateFrames):
						# Client spectate frames
						# Send spectator frames to every spectator
						for i in userToken.spectators:
							spectatorToken = glob.tokens.getTokenFromUserID(i)
							if (spectatorToken != None):
								spectatorToken.enqueue(serverPackets.spectatorFrames(packetData[7:]))
					elif (packetID == packetIDs.client_logout):
						# Logout packet, no parameters to read
						# TODO: Channel part at logout
						# Delete token
						glob.tokens.deleteToken(requestToken)

						# Enqueue our disconnection to everyone else
						glob.tokens.enqueueAll(serverPackets.userLogout(userID))

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
				consoleHelper.printColored("[!] "+requestToken+" user has been disconnected (invalid token)", bcolors.RED)

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
conf = config.config("config.ini")

if (conf.default == True):
	# We have generated a default config.ini, quit server
	consoleHelper.printWarning()
	consoleHelper.printColored("[!] config.ini not found. A default one has been generated.", bcolors.YELLOW)
	consoleHelper.printColored("[!] Please edit your config.ini and run the server again.", bcolors.YELLOW)
	sys.exit()

# If we haven't generated a default config.ini, check if it's valid
if (conf.checkConfig() == False):
	consoleHelper.printError()
	consoleHelper.printColored("[!] Invalid config.ini. Please configure it properly", bcolors.RED)
	consoleHelper.printColored("[!] Delete your config.ini to generate a default one", bcolors.RED)
	sys.exit()
else:
	consoleHelper.printDone()


# Connect to db
try:
	consoleHelper.printNoNl("> Connecting to MySQL db... ")
	glob.db = databaseHelper.db(conf.config["db"]["host"], conf.config["db"]["username"], conf.config["db"]["password"], conf.config["db"]["database"])
	consoleHelper.printDone()
except:
	# Exception while connecting to db
	consoleHelper.printError()
	consoleHelper.printColored("[!] Error while connection to database", bcolors.RED)
	consoleHelper.printColored("[!] Please check your config.ini and run the server again", bcolors.RED)
	sys.exit()

# Initialize chat channels
consoleHelper.printNoNl("> Initializing chat channels... ")
glob.channels.loadChannels()
consoleHelper.printDone()

# Get server parameters from config.ini
serverPort = int(conf.config["server"]["port"])
serverThreaded = stringToBool(conf.config["server"]["threaded"])
serverDebug = stringToBool(conf.config["server"]["debug"])
serverOutputPackets = stringToBool(conf.config["server"]["outputpackets"])

# Set flask debug mode
app.debug = serverDebug
flaskLogger.disabled = True

if (serverDebug == False):
	# Disable flask logger if we are not in debug mode
	#flaskLogger.disabled = True
	print("> Starting server...");
else:
	print("> Starting server in "+bcolors.YELLOW+"debug mode..."+bcolors.ENDC)

# Run server
app.run(host=conf.config["server"]["host"], port=serverPort, threaded=serverThreaded)

#except:
	# Server critical error handling
	# TODO: Fix this
	#consoleHelper.printColored("[!] Error while running server.", bcolors.RED)
	#consoleHelper.printColored("[!] The server has shut down unexpectedly.", bcolors.RED)
	#consoleHelper.printColored(str(sys.exc_info()[1]), bcolors.RED)
