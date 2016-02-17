# TODO: Remove useless imports
import struct
import flask
from flask import Flask, request	# TODO: Remove this import
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
import packetHelper
import responseBuilder
import config
import consoleHelper
import databaseHelper
import dataTypes
import passwordHelper
import userHelper


# Connected users
# slots[[token, userID], [token, userID], ...]
slots = [[]]

# Remove slot 0 because it's empty
slots.pop(0)


# Create flask instance
app = Flask(__name__)

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
	if request.method == 'POST':
		requestToken = request.headers.get('osu-token')	# Client's token
		requestData = str(request.data)					# Client's request data

		responseData = bytes()							# Server's response data
		responseToken = "";								# Server's response token

		if (requestToken == None):
			# We don't have a token, this is the first packet aka login
			print("> Accepting connection from "+request.remote_addr+"...")

			# Split POST body so we can get username/password/hardware data
			# We remove the first two and last three characters because they are
			# some escape stuff that we don't need
			loginData = requestData[2:-3].split("\\n")

			# Generate token and add it to connected tokens
			responseToken = str(uuid.uuid4())
			slots.append([responseToken, -1])

			print("> Generated token for "+loginData[0]+": "+responseToken)

			# Process login
			print("> Processing login request for "+loginData[0]+"...")
			try:
				userID = userHelper.getUserID(dbConnection, str(loginData[0]))
				if (userID == False):
					raise
				if (userHelper.checkLogin(dbConnection, userID, loginData[1]) == False):
					raise
				responseData += packets.notification("Logged in!")
				consoleHelper.printColored("> "+loginData[0]+" logged in", bcolors.GREEN)

				# TODO: Other login packets
			except:
				# TODO: Delete token when login fails
				consoleHelper.printColored("> "+loginData[0]+"'s login failed", bcolors.YELLOW)
				responseData += packets.loginFailed()

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
			#print(slots)

		# Send server's response to client
		return responseBuilder.response(responseToken, responseData).getResponse()
	else:
		# Not a POST request, send html page
		# TODO: Fix this crap
		html = 	"<html><head><title>MA MAURO ESISTE?</title><style type='text/css'>body{width:30%}</style></head><body><pre>"
		html += "           _                 __<br>"
		html += "          (_)              /  /<br>"
		html += "   ______ __ ____   ____  /  /____<br>"
		html += "  /  ___/  /  _  \\/  _  \\/  /  _  \\<br>"
		html += " /  /  /  /  /_) /  /_) /  /  ____/<br>"
		html += "/__/  /__/  .___/  .___/__/ \\_____/<br>"
		html += "        /  /   /  /<br>"
		html += "       /__/   /__/<br>"
		html += "<b>THERE'S A PYTHON ABROAD VERSION</b><br><br>"
		html += "<marquee style='white-space:pre;'><br>"
		html += "                          .. o  .<br>"
		html += "                         o.o o . o<br>"
		html += "                        oo...<br>"
		html += "                    __[]__<br>"
		html += "    phwr-->  _\\:D/_/o_o_o_|__     <span style=\"font-family: 'Comic Sans MS'; font-size: 8pt;\">u wot m8</span><br>"
		html += "             \\\"\"\"\"\"\"\"\"\"\"\"\"\"\"/<br>"
		html += "              \\ . ..  .. . /<br>"
		html += "^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^<br>"
		html += "</marquee><br><strike>reverse engineering a protocol impossible to reverse engineer since always</strike><br>we are actually reverse engineering bancho successfully. for the third time.</pre></body></html>"
		return html



# Server start
consoleHelper.printServerStartHeader(True);

# Read config.ini
consoleHelper.printNoNl("> Loading config file... ")
conf = config.config()

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
