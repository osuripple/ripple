import packetHelper
import dataTypes
import gameModes
import userHelper
import glob
import usernameColors

'''
SERVER PACKETS
'''
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

def channelJoined(channel):
	return packetHelper.buildPacket(64, [[channel, dataTypes.string]])

def userPanel(userID):
	# Get user data
	userToken = glob.tokens.getTokenFromUserID(userID)
	username = userHelper.getUserUsername(userID)
	timezone = 25	# TODO: Timezone and country
	country = 108
	gameRank = userHelper.getUserGameRank(userID, userToken.gameMode)
	latitude = userToken.getLatitude()
	longitude = userToken.getLongitude()

	# Get username color according to rank
	# Only admins and normal users are currently supported
	rank = userHelper.getUserRank(userID)
	if (rank == 4):
		usernameColor = usernameColors.admin
	else:
		usernameColor = usernameColors.normal


	return packetHelper.buildPacket(83,
	[
		[userID, dataTypes.sInt32],
		[username, dataTypes.string],
		[timezone, dataTypes.byte],
		[country, dataTypes.byte],
		[usernameColor, dataTypes.byte],
		[longitude, dataTypes.ffloat],
		[latitude, dataTypes.ffloat],
		[gameRank, dataTypes.uInt32]
	])


def userStats(userID):
	# Get userID's token from tokens list
	userToken = glob.tokens.getTokenFromUserID(userID)

	# Get stats from DB
	# TODO: Caching system
	rankedScore = 	userHelper.getUserRankedScore(userID, userToken.gameMode)
	accuracy = 		userHelper.getUserAccuracy(userID, userToken.gameMode)/100
	playcount = 	userHelper.getUserPlaycount(userID, userToken.gameMode)
	totalScore = 	userHelper.getUserTotalScore(userID, userToken.gameMode)
	gameRank = 		userHelper.getUserGameRank(userID, userToken.gameMode)
	pp = 0

	return packetHelper.buildPacket(11,
	[
		[userID, 				dataTypes.uInt32],
		[userToken.actionID, 	dataTypes.byte],
		[userToken.actionText, 	dataTypes.string],
		[userToken.actionMd5, 	dataTypes.string],
		[0, 					dataTypes.sInt32],	# Unknown
		[userToken.gameMode, 	dataTypes.byte],
		[0, 					dataTypes.sInt32],
		[rankedScore, 			dataTypes.uInt64],	# TODO: uInt64
		[accuracy, 				dataTypes.ffloat],
		[playcount, 			dataTypes.uInt32],
		[totalScore, 			dataTypes.uInt64],	# TODO: uInt64
		[gameRank,	 			dataTypes.uInt32],
		[pp, 					dataTypes.uInt16]
	])


# Chat packets
def publicMessage(fro, to, message):
	return packetHelper.buildPacket(7, [[fro, dataTypes.string], [message, dataTypes.string], [to, dataTypes.string]])

def channelInfo(channel):
	return packetHelper.buildPacket(65, [[channel, dataTypes.string], [glob.channels.channels[channel][0], dataTypes.string], [glob.channels.getConnectedUsers(channel), dataTypes.uInt16]])

# Other packets
def notification(message):
	return packetHelper.buildPacket(24, [[message, dataTypes.string]])

def jumpscare(message):
	return packetHelper.buildPacket(105, [[message, dataTypes.string]])

def banchoRestart():
	return packetHelper.buildPacket(86)

# Testing stuff
def openChat():
	return packetHelper.buildPacket(23)

def packet80():
	return packetHelper.buildPacket(80)





'''
CLIENT PACKETS
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
