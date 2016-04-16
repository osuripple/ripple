import clientPackets
import glob

def handle(userToken, packetData):
	# Read packet data. Same structure as changeMatchSettings
	packetData = clientPackets.changeMatchSettings(packetData)

	# Make sure the match exists
	matchID = userToken.matchID
	if matchID not in glob.matches.matches:
		return

	# Get our match
	match = glob.matches.matches[matchID]

	# Update match password
	match.changePassword(packetData["matchPassword"])
