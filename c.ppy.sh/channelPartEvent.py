"""
Event called when someone parts a channel
"""

import consoleHelper
import bcolors
import glob
import clientPackets

def handle(userToken, packetData):
	# Channel part packet
	packetData = clientPackets.channelPart(packetData)
	partChannel(userToken, packetData["channel"])

def partChannel(userToken, channelName):
	# Get usertoken data
	username = userToken.username
	userID = userToken.userID

	# Remove us from joined users and joined channels
	if (channelName in glob.channels.channels):
		userToken.partChannel(channelName)

		# Check if user is in channel
		if (userID in glob.channels.channels[channelName].connectedUsers):
			glob.channels.channels[channelName].userPart(userID)

		# Console output
		consoleHelper.printColored("> {} parted channel {}".format(username, channelName), bcolors.YELLOW)
