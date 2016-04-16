import glob
import clientPackets

def handle(userToken, packetData):
	# Get packet data
	packetData = clientPackets.transferHost(packetData)

	# Get match ID and match object
	matchID = userToken.matchID

	# Make sure we are in a match
	if matchID == -1:
		return

	# Make sure the match exists
	if matchID not in glob.matches.matches:
		return

	# Match exists, get object
	match = glob.matches.matches[matchID]

	# Transfer host
	match.transferHost(packetData["slotID"])
