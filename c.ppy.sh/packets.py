import packetHelper
import dataTypes


# Login errors
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
def userID(__uid):
	return packetHelper.buildPacket(5, [[__uid, dataTypes.sInt32]])

def silenceEndTime(__seconds):
	return packetHelper.buildPacket(92, [[__seconds, dataTypes.uInt32]])

def silenceEndTime(__seconds):
	return packetHelper.buildPacket(92, [[__seconds, dataTypes.uInt32]])


# Other packets
def notification(__message):
	return packetHelper.buildPacket(24, [[__message, dataTypes.string]])

def jumpscare(__message):
	return packetHelper.buildPacket(105, [[__message, dataTypes.string]])


# Testing stuff
def openChat():
	return packetHelper.buildPacket(23)

def packet80():
	return packetHelper.buildPacket(80)
