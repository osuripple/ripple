"""Hello, pep.py here, ex-owner of ripple and prime minister of Ripwot."""
# TODO: Remove useless imports
# TODO: Docs
import logging
import sys
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
import exceptions
import glob
import fokabot
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
import loginEvent
import setAwayMessageEvent

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
		# Client's token string and request data
		requestTokenString = flask.request.headers.get('osu-token')
		requestData = flask.request.data

		# Server's token string and request data
		responseTokenString = "ayy"
		responseData = bytes()

		if (requestTokenString == None):
			# No token, first request. Handle login.
			responseTokenString, responseData = loginEvent.handle(flask.request)
		else:
			try:
				# This is not the first packet, send response based on client's request
				# Packet start position, used to read stacked packets
				pos = 0

				# Make sure the token exists
				if (requestTokenString not in glob.tokens.tokens):
					raise exceptions.tokenNotFoundException()

				# Token exists, get its object
				userToken = glob.tokens.tokens[requestTokenString]

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
						consoleHelper.printColored("Incoming packet ({})({}):".format(requestTokenString, userToken.username), bcolors.GREEN)
						consoleHelper.printColored("Packet code: {}\nPacket length: {}\nSingle packet data: {}\n".format(str(packetID), str(dataLength), str(packetData)), bcolors.YELLOW)

					# Event handler
					def handleEvent(ev):
						def wrapper():
							ev.handle(userToken, packetData)
						return wrapper

					eventHandler = {
						packetIDs.client_sendPublicMessage: handleEvent(sendPublicMessageEvent),
						packetIDs.client_sendPrivateMessage: handleEvent(sendPrivateMessageEvent),
						packetIDs.client_setAwayMessage: handleEvent(setAwayMessageEvent),
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
							consoleHelper.printColored("[!] Unknown packet id from {} ({})".format(requestTokenString, packetID), bcolors.RED)

					# Update pos so we can read the next stacked packet
					# +7 because we add packet ID bytes, unused byte and data length bytes
					pos += dataLength+7

				# Token queue built, send it
				responseTokenString = userToken.token
				responseData = userToken.queue
				userToken.resetQueue()

				# Update ping time for timeout
				userToken.updatePingTime()
			except exceptions.tokenNotFoundException:
				# Token not found. Disconnect that user
				responseData = serverPackets.loginError()
				responseData += serverPackets.notification("Whoops! Something went wrong, please login again.")
				consoleHelper.printColored("[!] Received packet from unknown token ({}).".format(requestTokenString), bcolors.RED)
				consoleHelper.printColored("> {} have been disconnected (invalid token)".format(requestTokenString), bcolors.YELLOW)

		# Send server's response to client
		# We don't use token object because we might not have a token (failed login)
		return responseHelper.generateResponse(responseTokenString, responseData)
	else:
		# Not a POST request, send html page
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

	# Localize warning
	if(generalFunctions.stringToBool(glob.conf.config["server"]["localizeusers"]) == False):
		consoleHelper.printColored("[!] Warning! users localization is disabled!", bcolors.YELLOW)

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
