import glob
import clientPackets
import matchModModes

def handle(userToken, packetData):
	# Get token data
	userID = userToken.userID

	# Get packet data
	packetData = clientPackets.changeMods(packetData)

	# Make sure the match exists
	matchID = userToken.matchID
	if (matchID not in glob.matches.matches):
		return
	match = glob.matches.matches[matchID]

	# Set slot or match mods according to modType
	if (match.matchModMode == matchModModes.freeMod):
		# Freemod, set slot mods
		slotID = match.getUserSlotID(userID)
		if (slotID != None):
			match.setSlotMods(slotID, packetData["mods"])
	else:
		# Not freemod, set match mods
		match.changeMatchMods(packetData["mods"])
