import packetHelper
import dataTypes
import gameModes
import userHelper
import glob
import usernameColors
import consoleHelper
import bcolors
import packetIDs

'''
Write server packets functions
'''
# Login errors
# (userID packets derivates)
def loginFailed():
	return packetHelper.buildPacket(packetIDs.server_userID, [[-1, dataTypes.sInt32]])

def forceUpdate():
	return packetHelper.buildPacket(packetIDs.server_userID, [[-2, dataTypes.sInt32]])

def loginBanned():
	return packetHelper.buildPacket(packetIDs.server_userID, [[-3, dataTypes.sInt32]])

def loginError():
	return packetHelper.buildPacket(packetIDs.server_userID, [[-5, dataTypes.sInt32]])

def needSupporter():
	return packetHelper.buildPacket(packetIDs.server_userID, [[-6, dataTypes.sInt32]])


# Login packets
def userID(uid):
	return packetHelper.buildPacket(packetIDs.server_userID, [[uid, dataTypes.sInt32]])

def silenceEndTime(seconds):
	return packetHelper.buildPacket(packetIDs.server_silenceEnd, [[seconds, dataTypes.uInt32]])

def protocolVersion(version = 19):
	return packetHelper.buildPacket(packetIDs.server_protocolVersion, [[version, dataTypes.uInt32]])

def userSupporterGMT(supporter, GMT):
	result = 1;
	if (supporter == True):
		result += 4
	if (GMT == True):
		result += 2
	return packetHelper.buildPacket(packetIDs.server_supporterGMT, [[result, dataTypes.uInt32]])

def friendList(userID):
	friendsData = []

	# Get friend IDs from db
	friends = userHelper.getFriendList(userID)

	# Friends number
	friendsData.append([len(friends), dataTypes.uInt16])

	# Add all friend user IDs to friendsData
	for i in friends:
		friendsData.append([i, dataTypes.sInt32])

	return packetHelper.buildPacket(packetIDs.server_friendsList, friendsData)

def userLogout(userID):
	return packetHelper.buildPacket(packetIDs.server_userLogout, [[userID, dataTypes.sInt32], [0, dataTypes.byte]])

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


	return packetHelper.buildPacket(packetIDs.server_userPanel,
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

	return packetHelper.buildPacket(packetIDs.server_userStats,
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
def sendMessage(fro, to, message):
	return packetHelper.buildPacket(packetIDs.server_sendMessage, [[fro, dataTypes.string], [message, dataTypes.string], [to, dataTypes.string]])

def channelJoinSuccess(userID, chan):
	return packetHelper.buildPacket(packetIDs.server_channelJoinSuccess, [[chan, dataTypes.string]])

def channelInfo(chan):
	channel = glob.channels.channels[chan]
	return packetHelper.buildPacket(packetIDs.server_channelInfo, [[chan, dataTypes.string], [channel.description, dataTypes.string], [channel.getConnectedUsersCount(), dataTypes.uInt16]])

def channelInfoEnd():
	return packetHelper.buildPacket(packetIDs.server_channelInfoEnd)


# Spectator packets
def addSpectator(userID):
	return packetHelper.buildPacket(packetIDs.server_spectatorJoined, [[userID, dataTypes.sInt32]])

def removeSpectator(userID):
	return packetHelper.buildPacket(packetIDs.server_spectatorLeft, [[userID, dataTypes.sInt32]])

def spectatorFrames(data):
	return packetHelper.buildPacket(packetIDs.server_spectateFrames, [[data, dataTypes.bbytes]])

def noSongSpectator(userID):
	return packetHelper.buildPacket(packetIDs.server_spectatorCantSpectate, [[userID, dataTypes.sInt32]])


# Other packets
def notification(message):
	return packetHelper.buildPacket(packetIDs.server_notification, [[message, dataTypes.string]])

def jumpscare(message):
	return packetHelper.buildPacket(packetIDs.server_jumpscare, [[message, dataTypes.string]])

def banchoRestart():
	return packetHelper.buildPacket(packetIDs.server_restart)

# Testing stuff
def getAttention():
	return packetHelper.buildPacket(packetIDs.server_getAttention)

def packet80():
	return packetHelper.buildPacket(packetIDs.server_topBotnet)
