import packetHelper
import dataTypes
import gameModes
import userHelper


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

def userPanel(userID):
	username = "Nyo"
	timezone = 25
	country = 108
	usernameColor = 0
	gameRank = 1
	return packetHelper.buildPacket(83, [[userID, dataTypes.sInt32], [username, dataTypes.string], [timezone, dataTypes.byte], [country, dataTypes.byte], [usernameColor, dataTypes.byte], [0, dataTypes.sInt32], [0, dataTypes.sInt32], [gameRank, dataTypes.uInt32]])


def userStats(userID):
	actionID = 0				# TODO: Read action id, text and md5 from token
	actionText = "Ayy lmao"
	actionMd5 = "md5here"
	gameMode = gameModes.std

	rankedScore = 	userHelper.getUserRankedScore(userID, gameMode)
	accuracy = 		userHelper.getUserAccuracy(userID, gameMode)/100
	playcount = 	userHelper.getUserPlaycount(userID, gameMode)
	totalScore = 	userHelper.getUserTotalScore(userID, gameMode)
	gameRank = 		userHelper.getUserGameRank(userID, gameMode)
	pp = 0
	return packetHelper.buildPacket(11,
	[
		[userID, 		dataTypes.uInt32],
		[actionID, 		dataTypes.byte],
		[actionText, 	dataTypes.string],
		[actionMd5, 	dataTypes.string],
		[0, 			dataTypes.sInt32],	# Unknown
		[gameMode, 		dataTypes.byte],
		[0, 			dataTypes.sInt32],
		[rankedScore, 	dataTypes.uInt32],	# TODO: uInt64
		[0, 			dataTypes.sInt32],	# other 4 bytes of int64
		[accuracy, 		dataTypes.ffloat],
		[playcount, 	dataTypes.uInt32],
		[totalScore, 	dataTypes.uInt32],	# TODO: uInt64
		[0, 			dataTypes.sInt32],	# other 4 bytes of int64
		[gameRank,	 	dataTypes.uInt32],
		[pp, 			dataTypes.uInt16]
	])


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
