import clientPackets
import glob

def handle(userToken, packetData):
	# Read token and packet data
	userID = userToken.userID
	packetData = clientPackets.matchInvite(packetData)

	# Get match ID and match object
	matchID = userToken.matchID

	# Make sure we are in a match
	if matchID == -1:
		return

	# Make sure the match exists
	if matchID not in glob.matches.matches:
		return

	# Get match object
	match = glob.matches.matches[matchID]

	# Send invite
	match.invite(userID, packetData["userID"])
