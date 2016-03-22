"""
Event called when someone parts a channel
"""

import consoleHelper
import bcolors
import glob
import clientPackets

def handle(userToken, packetData):
	# Get usertoken data
	username = userToken.username
	userID = userToken.userID

	# Channel part packet
	packetData = clientPackets.channelPart(packetData)

	# Remove us from joined users and joined channels
	if packetData["channel"] in glob.channels.channels:
		userToken.partChannel(packetData["channel"])

		# TODO: check if user is in channel
		glob.channels.channels[packetData["channel"]].userPart(userID)

		# Console output
		consoleHelper.printColored("> {} parted channel {}".format(username, packetData["channel"]), bcolors.YELLOW)
