# TODO: Remove useless imports
# TODO: Use __memes only on classes
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

# pep.py files
import bcolors
import packets
import config
import dataTypes
import userHelper
import osuToken
import tokenList
import exceptions

import packetHelper
import consoleHelper
import databaseHelper
import passwordHelper
import responseHelper

# Create token list
tokens = tokenList.tokenList()

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

# Main bancho server
@app.route("/", methods=['GET', 'POST'])
def banchoServer():
	if (flask.request.method == 'POST'):
		requestToken = flask.request.headers.get('osu-token')	# Client's token
		requestData = str(flask.request.data)					# Client's request data

		responseData = bytes()								# Server's response data
		responseTokenString = "ayy";						# Server's response token string

		if (requestToken == None):
			# We don't have a token, this is the first packet aka login
			print("> Accepting connection from "+flask.request.remote_addr+"...")

			# Split POST body so we can get username/password/hardware data
			# We remove the first two and last three characters because they are
			# some escape stuff that we don't need
			loginData = requestData[2:-3].split("\\n")

			# Process login
			print("> Processing login request for "+loginData[0]+"...")
			try:
				# Try to get the ID
				userID = userHelper.getUserID(dbConnection, str(loginData[0]))
				if (userID == False):
					# Invalid username
					raise exceptions.loginFailedException()
				if (userHelper.checkLogin(dbConnection, userID, loginData[1]) == False):
					# Invalid password
					raise exceptions.loginFailedException()

				# Make sure we are not banned
				userAllowed = userHelper.getUserAllowed(dbConnection, userID)
				if (userAllowed == 0):
					# Banned
					raise exceptions.loginBannedException()

				# Delete old tokens for that user and generate a new one
				tokens.deleteOldTokens(userID)
				tokens.addToken(userID)

				# Get our new token object
				responseToken = tokens.getTokenFromUserID(userID)

				# Send all needed login packets
				responseData = packets.silenceEndTime(0)
				responseData += packets.userID(userID)
				responseData += packets.protocolVersion()
				responseData += packets.userSupporterGMT(True, True)
				responseData += packets.channelJoin("#osu")
				responseData += packets.notification("Logged in!")

				# Print logged in message
				# TODO: Output token too
				consoleHelper.printColored("> "+loginData[0]+" logged in", bcolors.GREEN)

				# Set reponse data and tokenstring to right value and reset our queue
				responseTokenString = responseToken.token
				#responseData = responseToken.queue
				#reponseToken.resetQueue()
			except exceptions.loginFailedException:
				# Login failed error packet
				# (we don't use enqueue because we don't have a token since login has failed)
				responseData += packets.loginFailed()
			except exceptions.loginBannedException:
				# Login banned error packet
				responseData += packets.loginBanned()
			finally:
				# Print login failed message to console
				consoleHelper.printColored("> "+loginData[0]+"'s login failed", bcolors.YELLOW)


			#responseData += packets.silenceEndTime(0)
			#responseData += packets.jumpscare("BANCHOBOT WILL KILL YOU\nHE IS EVIL\n...")
			#responseData += packetHelper.buildPacket(packets.silenceEndTime, {"endTime": 0})
			#responseData += packetHelper.buildPacket(packets.userID, {"userID": 1337})
			#responseData += packetHelper.buildPacket(packets.protocolVersion)
			#responseData += packetHelper.buildPacket(packets.userSupporterQAT, {"rank": 6})
			#responseData += packetHelper.buildPacket(packets.userpanel)
			#responseData += packetHelper.buildPacket(packets.onlineUsers, {"count": 2, "u1": 0, "u2": 1337})
			#responseData += packetHelper.buildPacket(packets.unknown1)
			#responseData += packetHelper.buildPacket(packets.mainChannel, {"channel": "#osu"})
			#responseData += packetHelper.buildPacket(packets.notification, {"message": "Welcome to the pep.py server!"})
			#print(tokens)

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
	#dbConnection = pymysql.connect(host=conf.config["db"]["host"], user=conf.config["db"]["username"], password=conf.config["db"]["password"], db=conf.config["db"]["database"], cursorclass=pymysql.cursors.DictCursor)
	dbConnection = databaseHelper.db(conf.config["db"]["host"], conf.config["db"]["username"], conf.config["db"]["password"], conf.config["db"]["database"])
	consoleHelper.printDone()
except:
	# Exception while connecting to db
	consoleHelper.printError()
	consoleHelper.printColored("[!] Error while connection to database", bcolors.RED)
	consoleHelper.printColored("[!] Please check your config.ini and run the server again", bcolors.RED)
	sys.exit()

# Start HTTP server
try:
	# Get server parameters from config.ini
	serverPort = int(conf.config["server"]["port"])
	serverThreaded = stringToBool(conf.config["server"]["threaded"])
	serverDebug = stringToBool(conf.config["server"]["debug"])

	# Set flask debug mode
	app.debug = serverDebug

	if (serverDebug == False):
		# Disable flask logger if we are not in debug mode
		flaskLogger.disabled = True
		print("> Starting server...");
	else:
		print("> Starting server in "+bcolors.YELLOW+"debug mode..."+bcolors.ENDC)

	# Run server
	app.run(host=conf.config["server"]["host"], port=serverPort, threaded=serverThreaded)
except:
	# Server critical error handling
	# TODO: Fix this
	consoleHelper.printColored("[!] Error while running server.", bcolors.RED)
	consoleHelper.printColored("[!] The server has shut down unexpectedly.", bcolors.RED)
	consoleHelper.printColored(str(sys.exc_info()[1]), bcolors.RED)
