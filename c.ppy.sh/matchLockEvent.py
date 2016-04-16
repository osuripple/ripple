import glob
import clientPackets

def handle(userToken, packetData):
	# Get token data
	userID = userToken.userID

	# Get packet data
	packetData = clientPackets.lockSlot(packetData)

	# Make sure the match exists
	matchID = userToken.matchID
	if matchID not in glob.matches.matches:
		return
	match = glob.matches.matches[matchID]

	# Make sure we aren't locking our slot
	ourSlot = match.getUserSlotID(userID)
	if packetData["slotID"] == ourSlot:
		return

	# Lock/Unlock slot
	match.toggleSlotLock(packetData["slotID"])
