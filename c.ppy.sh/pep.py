"""Hello, pep.py here, ex-owner of ripple and prime minister of Ripwot."""
# TODO: Remove useless imports
# TODO: Docs
import logging
import sys
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
import config
import userHelper
import exceptions
import locationHelper
import glob
import fokabot
import countryHelper
import banchoConfig

import sendPublicMessageEvent
import sendPrivateMessageEvent
import channelJoinEvent
import channelPartEvent
import changeActionEvent
import cantSpectateEvent
import startSpectatingEvent
import stopSpectatingEvent
import spectateFramesEvent
import friendAddEvent
import friendRemoveEvent
import logoutEvent

# pep.py helpers
import packetHelper
import consoleHelper
import databaseHelper
import responseHelper
import generalFunctions
import systemHelper

# Create flask instance
app = flask.Flask(__name__)

# Get flask logger
flaskLogger = logging.getLogger("werkzeug")

# Ci trigger
@app.route("/ci-trigger")
@app.route("/api/ci-trigger")
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
		return flask.jsonify({"response" : "-1"})

	# Ci event triggered, schedule server shutdown
	consoleHelper.printColored("[!] Ci event triggered from {}".format(requestIP), bcolors.PINK)
	systemHelper.scheduleShutdown(5, False, "A new Bancho update is available and the server will be restarted in 5 seconds. Thank you for your patience.")

	return flask.jsonify({"response" : "1"})

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
		responseTokenString = "ayy"

		if (requestToken == None):
			# We don't have a token, this is the first packet aka login
			print("> Accepting connection from {}...".format(requestIP))

			# Split POST body so we can get username/password/hardware data
			loginData = str(requestData)[2:-3].split("\\n")

			# Process login
			print("> Processing login request for {}...".format(loginData[0]))
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
				consoleHelper.printColored("> {} logged in ({})".format(loginData[0], responseToken.token), bcolors.GREEN)

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
					consoleHelper.printColored("> {}'s login failed".format(loginData[0]), bcolors.YELLOW)
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
						consoleHelper.printColored("Incoming packet ({})({}):".format(requestToken, userToken.username), bcolors.GREEN)
						consoleHelper.printColored("Packet code: {}\nPacket length: {}\nSingle packet data: {}\n".format(str(packetID), str(dataLength), str(packetData)), bcolors.YELLOW)

					# Event handler
					def handleEvent(ev):
						def wrapper():
							ev.handle(userToken, packetData)
						return wrapper

					eventHandler = {
						packetIDs.client_sendPublicMessage: handleEvent(sendPublicMessageEvent),
						packetIDs.client_sendPrivateMessage: handleEvent(sendPrivateMessageEvent),
						packetIDs.client_channelJoin: handleEvent(channelJoinEvent),
						packetIDs.client_channelPart: handleEvent(channelPartEvent),
						packetIDs.client_changeAction: handleEvent(changeActionEvent),
						packetIDs.client_startSpectating: handleEvent(startSpectatingEvent),
						packetIDs.client_stopSpectating: handleEvent(stopSpectatingEvent),
						packetIDs.client_cantSpectate: handleEvent(cantSpectateEvent),
						packetIDs.client_spectateFrames: handleEvent(spectateFramesEvent),
						packetIDs.client_friendAdd: handleEvent(friendAddEvent),
						packetIDs.client_friendRemove: handleEvent(friendRemoveEvent),
						packetIDs.client_logout: handleEvent(logoutEvent)
					}

					if packetID != 4:
						if packetID in eventHandler:
							eventHandler[packetID]()
						else:
							consoleHelper.printColored("[!] Unknown packet id from {} ({})".format(requestToken, packetID), bcolors.RED)

					# Update pos so we can read the next stacked packet
					# +7 because we add packet ID bytes, unused byte and data length bytes
					pos += dataLength+7
				# WHILE END

				# Token queue built, send it
				# TODO: Move somewhere else
				responseTokenString = userToken.token
				responseData = userToken.queue
				userToken.resetQueue()

				# Update ping time for timeout
				userToken.updatePingTime()
			except exceptions.tokenNotFoundException:
				# Token not found. Disconnect that user
				responseData = serverPackets.loginError()
				responseData += serverPackets.notification("Whoops! Something went wrong, please login again.")
				consoleHelper.printColored("[!] Received packet from unknown token ({}).".format(requestToken), bcolors.RED)
				consoleHelper.printColored("> {} have been disconnected (invalid token)".format(requestToken), bcolors.YELLOW)

		# Send server's response to client
		# We don't use token object because we might not have a token (failed login)
		return responseHelper.generateResponse(responseTokenString, responseData)
	else:
		# Not a POST request, send html page
		# TODO: Fix this crap
		return responseHelper.HTMLResponse()


if (__name__ == "__main__"):
	# Server start
	consoleHelper.printServerStartHeader(True)

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
		glob.db = databaseHelper.db(glob.conf.config["db"]["host"], glob.conf.config["db"]["username"], glob.conf.config["db"]["password"], glob.conf.config["db"]["database"], int(glob.conf.config["db"]["pingtime"]))
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

	# Initialize user timeout check loop
	try:
		consoleHelper.printNoNl("> Initializing user timeout check loop... ")
		glob.tokens.usersTimeoutCheckLoop(int(glob.conf.config["server"]["timeouttime"]), int(glob.conf.config["server"]["timeoutlooptime"]))
		consoleHelper.printDone()
	except:
		consoleHelper.printError()
		consoleHelper.printColored("[!] Error while initializing user timeout check loop", bcolors.RED)
		consoleHelper.printColored("[!] Make sure that 'timeouttime' and 'timeoutlooptime' in config.ini are numbers", bcolors.RED)
		raise

	# Get server parameters from config.ini
	serverName = glob.conf.config["server"]["server"]
	serverHost = glob.conf.config["server"]["host"]
	serverPort = int(glob.conf.config["server"]["port"])
	serverOutputPackets = generalFunctions.stringToBool(glob.conf.config["server"]["outputpackets"])

	# Run server sanic way
	if (serverName == "tornado"):
		# Tornado server
		print("> Starting tornado...")
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
			print("> Starting flask...")
		else:
			print("> Starting flask in "+bcolors.YELLOW+"debug mode..."+bcolors.ENDC)

		# Run flask server
		app.run(host=serverHost, port=serverPort, threaded=flaskThreaded)
	else:
		print(bcolors.RED+"[!] Unknown server. Please set the server key in config.ini to "+bcolors.ENDC+bcolors.YELLOW+"tornado"+bcolors.ENDC+bcolors.RED+" or "+bcolors.ENDC+bcolors.YELLOW+"flask"+bcolors.ENDC)
		sys.exit()
