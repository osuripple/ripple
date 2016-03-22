import clientPackets
import glob
import consoleHelper
import bcolors

def handle(userToken, packetData):
	# Get usertoken data
	userID = userToken.userID
	username = userToken.username

	# Read packet data
	packetData = clientPackets.changeSlot(packetData)

	# Get match
	match = glob.matches.getMatchFromMatchID(userToken.matchID)
	if (match != None):
		# Change slot
		match.userChangeSlot(userID, packetData["slotID"])
		consoleHelper.printColored("> MPROOM{}: {} moved to slot {}".format(match.matchID, username, packetData["slotID"]), bcolors.BLUE)

		# Update match
		match.sendUpdate()
