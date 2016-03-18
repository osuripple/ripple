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
	return packetHelper.readPacketData(stream,[["userID", 	dataTypes.sInt32]])
