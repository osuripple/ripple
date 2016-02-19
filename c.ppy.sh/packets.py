import packetHelper
import dataTypes


# Login errors
# (userID packets derivates)
def loginFailed():
	return packetHelper.buildPacket(5, [[-1, dataTypes.sInt32]])

def forceUpdate():
	return packetHelper.buildPacket(5, [[-2, dataTypes.sInt32]])

def loginBanned():
	return packetHelper.buildPacket(5, [[-3, dataTypes.sInt32]])

def loginError():
	return packetHelper.buildPacket(5, [[-5, dataTypes.sInt32]])

def needSupporter():
	return packetHelper.buildPacket(5, [[-6, dataTypes.sInt32]])


# Login packets
def userID(uid):
	return packetHelper.buildPacket(5, [[uid, dataTypes.sInt32]])

def silenceEndTime(seconds):
	return packetHelper.buildPacket(92, [[seconds, dataTypes.uInt32]])

def protocolVersion(version = 19):
	return packetHelper.buildPacket(75, [[version, dataTypes.uInt32]])

def userSupporterGMT(supporter, GMT):
	result = 1;
	if (supporter == True):
		result += 4
	if (GMT == True):
		result += 2
	return packetHelper.buildPacket(71, [[result, dataTypes.uInt32]])

def channelJoin(channel):
	return packetHelper.buildPacket(64, [[channel, dataTypes.string]])


# Other packets
def notification(message):
	return packetHelper.buildPacket(24, [[message, dataTypes.string]])

def jumpscare(message):
	return packetHelper.buildPacket(105, [[message, dataTypes.string]])


# Testing stuff
def openChat():
	return packetHelper.buildPacket(23)

def packet80():
	return packetHelper.buildPacket(80)
