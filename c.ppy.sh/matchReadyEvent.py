import glob

def handle(userToken, _):
	# Get usertoken data
	userID = userToken.userID

	# Make sure the match exists
	matchID = userToken.matchID
	if matchID not in glob.matches.matches:
		return
	match = glob.matches.matches[matchID]

	# Get our slotID and change ready status
	slotID = match.getUserSlotID(userID)
	if slotID != None:
		match.toggleSlotReady(slotID)
