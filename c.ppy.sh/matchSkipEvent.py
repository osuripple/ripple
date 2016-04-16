import glob

def handle(userToken, packetData):
	# Get userToken data
	userID = userToken.userID

	# Get match ID and match object
	matchID = userToken.matchID

	# Make sure we are in a match
	if matchID == -1:
		return

	# Make sure the match exists
	if matchID not in glob.matches.matches:
		return

	# The match exists, get object
	match = glob.matches.matches[matchID]

	# Skip
	match.playerSkip(userID)
