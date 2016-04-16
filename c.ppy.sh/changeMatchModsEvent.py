import glob
import clientPackets
import matchModModes
import mods

def handle(userToken, packetData):
	# Get token data
	userID = userToken.userID

	# Get packet data
	packetData = clientPackets.changeMods(packetData)

	# Make sure the match exists
	matchID = userToken.matchID
	if matchID not in glob.matches.matches:
		return
	match = glob.matches.matches[matchID]

	# Set slot or match mods according to modType
	if match.matchModMode == matchModModes.freeMod:
		# Freemod

		# Host can set global DT/HT
		if userID == match.hostUserID:
			# If host has selected DT/HT and Freemod is enabled, set DT/HT as match mod
			if (packetData["mods"] & mods.DoubleTime) > 0:
				match.changeMatchMods(mods.DoubleTime)
				# Nighcore
				if (packetData["mods"] & mods.Nightcore) > 0:
					match.changeMatchMods(match.mods+mods.Nightcore)
			elif (packetData["mods"] & mods.HalfTime) > 0:
				match.changeMatchMods(mods.HalfTime)
			else:
				# No DT/HT, set global mods to 0 (we are in freemod mode)
				match.changeMatchMods(0)

		# Set slot mods
		slotID = match.getUserSlotID(userID)
		if slotID != None:
			match.setSlotMods(slotID, packetData["mods"])
	else:
		# Not freemod, set match mods
		match.changeMatchMods(packetData["mods"])
