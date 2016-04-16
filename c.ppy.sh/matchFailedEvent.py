import glob

def handle(userToken, _):
	# Get usertoken data
	userID = userToken.userID

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

	# Fail user
	match.playerFailed(userID)
