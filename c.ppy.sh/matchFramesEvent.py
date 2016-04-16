import glob
import slotStatuses
import serverPackets

def handle(userToken, packetData):
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

	# The match exists, get object
	match = glob.matches.matches[matchID]

	# Change slot id in packetData
	slotID = match.getUserSlotID(userID)
	'''opd = packetData[4]
	packetData = bytearray(packetData)
	packetData[4] = slotID
	print("User: {}, slot {}, oldPackData: {}, packData {}".format(userID, slotID, opd, packetData[4]))'''

	# Enqueue frames to who's playing
	for i in range(0,16):
		if match.slots[i]["userID"] > -1 and match.slots[i]["status"] == slotStatuses.playing:
			token = glob.tokens.getTokenFromUserID(match.slots[i]["userID"])
			if token != None:
				token.enqueue(serverPackets.matchFrames(slotID, packetData))
