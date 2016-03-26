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
	match = glob.matches.matches[userToken.matchID]

	# Change slot
	match.userChangeSlot(userID, packetData["slotID"])
