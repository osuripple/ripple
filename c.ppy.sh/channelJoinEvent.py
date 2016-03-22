"""
Event called when someone joins a channel
"""

import clientPackets
import consoleHelper
import bcolors
import serverPackets
import glob
import exceptions

def handle(userToken, packetData):
	try:
		# Get usertoken data
		username = userToken.username
		userID = userToken.userID
		userRank = userToken.rank

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
		consoleHelper.printColored("> {} joined channel {}".format(username, packetData["channel"]), bcolors.GREEN)
	except exceptions.channelNoPermissionsException:
		consoleHelper.printColored("[!] {} attempted to join channel {}, but they have no read permissions".format(username, packetData["channel"]), bcolors.RED)
	except exceptions.channelUnknownException:
		consoleHelper.printColored("[!] {} attempted to join an unknown channel ({})".format(username, packetData["channel"]), bcolors.RED)
