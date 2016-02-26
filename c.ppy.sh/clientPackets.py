import dataTypes
import packetHelper

'''
Read client packets functions
'''
def userActionChange(stream):
	return packetHelper.readPacketData(stream,
	[
		["actionID", 	dataTypes.byte],
		["actionText", 	dataTypes.string],
		["actionMd5", 	dataTypes.string]
	])

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

def channelJoin(stream):
	return packetHelper.readPacketData(stream,[["channel", 	dataTypes.string]])

def channelPart(stream):
	return packetHelper.readPacketData(stream,[["channel", 	dataTypes.string]])


''' SPECTATOR PACKETS '''
def startSpectating(stream):
	return packetHelper.readPacketData(stream,[["userID", 	dataTypes.sInt32]])
