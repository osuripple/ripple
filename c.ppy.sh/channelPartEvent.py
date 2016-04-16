"""
Event called when someone parts a channel
"""

import consoleHelper
import bcolors
import glob
import clientPackets
import serverPackets

def handle(userToken, packetData):
	# Channel part packet
	packetData = clientPackets.channelPart(packetData)
	partChannel(userToken, packetData["channel"])

def partChannel(userToken, channelName, kick = False):
	# Get usertoken data
	username = userToken.username
	userID = userToken.userID

	# Remove us from joined users and joined channels
	if channelName in glob.channels.channels:
		# Check that user is in channel
		if channelName in userToken.joinedChannels:
			userToken.partChannel(channelName)

		# Check if user is in channel
		if userID in glob.channels.channels[channelName].connectedUsers:
			glob.channels.channels[channelName].userPart(userID)

		# Force close tab if needed
		if kick == True:
			userToken.enqueue(serverPackets.channelKicked(channelName))

		# Console output
		consoleHelper.printColored("> {} parted channel {}".format(username, channelName), bcolors.YELLOW)
