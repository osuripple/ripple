""" Contains functions used to read specific client packets from byte stream """
import dataTypes
import packetHelper


""" General packets """
def userActionChange(stream):
	return packetHelper.readPacketData(stream,
	[
		["actionID", 	dataTypes.byte],
		["actionText", 	dataTypes.string],
		["actionMd5", 	dataTypes.string],
		["actionMods",	dataTypes.uInt32],
		["gameMode",	dataTypes.byte]
	])



""" Client chat packets """
def sendPublicMessage(stream):
	return packetHelper.readPacketData(stream,
	[
		["unknown", 	dataTypes.string],
		["message", 	dataTypes.string],
		["to", 			dataTypes.string]
	])

def sendPrivateMessage(stream):
	return packetHelper.readPacketData(stream,
	[
		["unknown", 	dataTypes.string],
		["message", 	dataTypes.string],
		["to", 			dataTypes.string],
		["unknown2",	dataTypes.uInt32]
	])

def setAwayMessage(stream):
	return packetHelper.readPacketData(stream,
	[
		["unknown", 	dataTypes.string],
		["awayMessage", dataTypes.string]
	])

def channelJoin(stream):
	return packetHelper.readPacketData(stream,[["channel", 	dataTypes.string]])

def channelPart(stream):
	return packetHelper.readPacketData(stream,[["channel", 	dataTypes.string]])

def addRemoveFriend(stream):
	return packetHelper.readPacketData(stream, [["friendID", dataTypes.sInt32]])



""" SPECTATOR PACKETS """
def startSpectating(stream):
	return packetHelper.readPacketData(stream,[["userID", dataTypes.sInt32]])


""" MULTIPLAYER PACKETS """
def createMatch(stream):
	# Some settings
	struct = [
		["matchID", dataTypes.uInt16],	# always 0
		["inProgress", dataTypes.byte], # always 0
		["unknown", dataTypes.byte],	# always 0
		["mods", dataTypes.uInt32],
		["matchName", dataTypes.string],
		["matchPassword", dataTypes.string],
		["beatmapName", dataTypes.string],
		["beatmapID", dataTypes.uInt32],
		["beatmapMD5", dataTypes.string]
	]

	# Slot statuses
	for i in range(0,16):
		struct.append(["slot{}Status".format(str(i)), dataTypes.byte])

	# Slot teams
	for i in range(0,16):
		struct.append(["slot{}Team".format(str(i)), dataTypes.byte])

	# No slot user IDs because we have just created a new match
	# Other settings
	struct.extend([
		["hostUserID", dataTypes.sInt32],
		["gameMode", dataTypes.byte],
		["scoringType", dataTypes.byte],	# always 0
		["teamType", dataTypes.byte],		# always 0
		["freeMods", dataTypes.byte],		# always 0
		["seed", dataTypes.uInt32]
	])

	# Return data
	return packetHelper.readPacketData(stream, struct)

def changeSlot(stream):
	return packetHelper.readPacketData(stream, [["slotID", dataTypes.uInt32]])

def joinMatch(stream):
	return packetHelper.readPacketData(stream, [["matchID", dataTypes.uInt32]])
