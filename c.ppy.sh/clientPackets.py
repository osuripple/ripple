""" Contains functions used to read specific client packets from byte stream """
import dataTypes
import packetHelper
import slotStatuses


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
def matchSettings(stream):
	# Data to return, will be merged later
	data = []

	# Some settings
	struct = [
		["matchID", dataTypes.uInt16],
		["inProgress", dataTypes.byte],
		["unknown", dataTypes.byte],
		["mods", dataTypes.uInt32],
		["matchName", dataTypes.string],
		["matchPassword", dataTypes.string],
		["beatmapName", dataTypes.string],
		["beatmapID", dataTypes.uInt32],
		["beatmapMD5", dataTypes.string]
	]

	# Slot statuses (not used)
	for i in range(0,16):
		struct.append(["slot{}Status".format(str(i)), dataTypes.byte])

	# Slot statuses (not used)
	for i in range(0,16):
		struct.append(["slot{}Team".format(str(i)), dataTypes.byte])

	# Read first part
	data.append(packetHelper.readPacketData(stream, struct))

	# Skip userIDs because fuck
	start = 7+2+1+1+4+4+16+16+len(data[0]["matchName"])+len(data[0]["matchPassword"])+len(data[0]["beatmapMD5"])+len(data[0]["beatmapName"])
	start += 1 if (data[0]["matchName"] == "") else 2
	start += 1 if (data[0]["matchPassword"] == "") else 2
	start += 2	# If beatmap name and MD5 don't change, the client sends \x0b\x00 istead of \x00 only, so always add 2. ...WHY!
	start += 2
	for i in range(0,16):
		s = data[0]["slot{}Status".format(str(i))]
		if s != slotStatuses.free and s != slotStatuses.locked:
			start += 4

	# Other settings
	struct = [
		["hostUserID", dataTypes.sInt32],
		["gameMode", dataTypes.byte],
		["scoringType", dataTypes.byte],
		["teamType", dataTypes.byte],
		["freeMods", dataTypes.byte],
	]

	# Read last part
	data.append(packetHelper.readPacketData(stream[start:], struct, False))

	# Mods if freemod (not used)
	#if data[1]["freeMods"] == 1:

	result = {}
	for i in data:
		result.update(i)
	return result

def createMatch(stream):
	return matchSettings(stream)

def changeMatchSettings(stream):
	return matchSettings(stream)

def changeSlot(stream):
	return packetHelper.readPacketData(stream, [["slotID", dataTypes.uInt32]])

def joinMatch(stream):
	return packetHelper.readPacketData(stream, [["matchID", dataTypes.uInt32], ["password", dataTypes.string]])

def changeMods(stream):
	return packetHelper.readPacketData(stream, [["mods", dataTypes.uInt32]])

def lockSlot(stream):
	return packetHelper.readPacketData(stream, [["slotID", dataTypes.uInt32]])

def transferHost(stream):
	return packetHelper.readPacketData(stream, [["slotID", dataTypes.uInt32]])

def matchInvite(stream):
	return packetHelper.readPacketData(stream, [["userID", dataTypes.uInt32]])
