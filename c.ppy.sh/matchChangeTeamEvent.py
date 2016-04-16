import glob

def handle(userToken, _):
	# Read token data
	userID = userToken.userID

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

	# Change team
	match.changeTeam(userID)
