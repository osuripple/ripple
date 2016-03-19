""" Contains functions used to write specific server packets to byte streams """
import packetHelper
import dataTypes
import userHelper
import glob
import userRanks
import packetIDs

""" Login errors packets
(userID packets derivates) """
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


""" Login packets """
def userID(uid):
	return packetHelper.buildPacket(packetIDs.server_userID, [[uid, dataTypes.sInt32]])

def silenceEndTime(seconds):
	return packetHelper.buildPacket(packetIDs.server_silenceEnd, [[seconds, dataTypes.uInt32]])

def protocolVersion(version = 19):
	return packetHelper.buildPacket(packetIDs.server_protocolVersion, [[version, dataTypes.uInt32]])

def mainMenuIcon(icon):
	return packetHelper.buildPacket(packetIDs.server_mainMenuIcon, [[icon, dataTypes.string]])

def userSupporterGMT(supporter, GMT):
	result = 1
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

def onlineUsers():
	onlineUsersData = []

	users = glob.tokens.tokens

	# Users number
	onlineUsersData.append([len(users), dataTypes.uInt16])

	# Add all users user IDs to onlineUsersData
	for _,value in users.items():
		onlineUsersData.append([value.userID, dataTypes.sInt32])

	return packetHelper.buildPacket(packetIDs.server_userPresenceBundle, onlineUsersData)


""" Users packets """
def userLogout(userID):
	return packetHelper.buildPacket(packetIDs.server_userLogout, [[userID, dataTypes.sInt32], [0, dataTypes.byte]])

def userPanel(userID):
	# Get user data
	userToken = glob.tokens.getTokenFromUserID(userID)
	username = userHelper.getUserUsername(userID)
	timezone = 24	# TODO: Timezone
	country = userToken.getCountry()
	gameRank = userHelper.getUserGameRank(userID, userToken.gameMode)
	latitude = userToken.getLatitude()
	longitude = userToken.getLongitude()

	# Get username color according to rank
	# Only admins and normal users are currently supported
	rank = userHelper.getUserRank(userID)
	if (username == "FokaBot"):
		userRank = userRanks.mod
	elif (rank == 4):
		userRank = userRanks.admin
	else:
		userRank = userRanks.normal


	return packetHelper.buildPacket(packetIDs.server_userPanel,
	[
		[userID, dataTypes.sInt32],
		[username, dataTypes.string],
		[timezone, dataTypes.byte],
		[country, dataTypes.byte],
		[userRank, dataTypes.byte],
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
		[userToken.actionMods,	dataTypes.sInt32],
		[userToken.gameMode, 	dataTypes.byte],
		[0, 					dataTypes.sInt32],
		[rankedScore, 			dataTypes.uInt64],
		[accuracy, 				dataTypes.ffloat],
		[playcount, 			dataTypes.uInt32],
		[totalScore, 			dataTypes.uInt64],
		[gameRank,	 			dataTypes.uInt32],
		[pp, 					dataTypes.uInt16]
	])


""" Chat packets """
def sendMessage(fro, to, message):
	return packetHelper.buildPacket(packetIDs.server_sendMessage, [[fro, dataTypes.string], [message, dataTypes.string], [to, dataTypes.string], [userHelper.getUserID(fro), dataTypes.sInt32]])

def channelJoinSuccess(userID, chan):
	return packetHelper.buildPacket(packetIDs.server_channelJoinSuccess, [[chan, dataTypes.string]])

def channelInfo(chan):
	channel = glob.channels.channels[chan]
	return packetHelper.buildPacket(packetIDs.server_channelInfo, [[chan, dataTypes.string], [channel.description, dataTypes.string], [channel.getConnectedUsersCount(), dataTypes.uInt16]])

def channelInfoEnd():
	return packetHelper.buildPacket(packetIDs.server_channelInfoEnd, [[0, dataTypes.uInt32]])


""" Spectator packets """
def addSpectator(userID):
	return packetHelper.buildPacket(packetIDs.server_spectatorJoined, [[userID, dataTypes.sInt32]])

def removeSpectator(userID):
	return packetHelper.buildPacket(packetIDs.server_spectatorLeft, [[userID, dataTypes.sInt32]])

def spectatorFrames(data):
	return packetHelper.buildPacket(packetIDs.server_spectateFrames, [[data, dataTypes.bbytes]])

def noSongSpectator(userID):
	return packetHelper.buildPacket(packetIDs.server_spectatorCantSpectate, [[userID, dataTypes.sInt32]])


""" Multiplayer Packets """
def matchNew():
	matchID = 1337
	inProgress = False
	matchType = 0
	mods = 0
	matchName = "Staccah staccah!"
	matchPassword = ""
	beatmapName = "Nice artist - Nice beatmap [Nice difficulty]"
	beatmapID = 1337
	beatmapMD5 = "9c2f924fb2f7004e7979ab2027ca1d65"
	slotStatus = []
	for i in range(0,16):
		slotStatus.append(0)

	slotTeam = []
	for i in range(0,16):
		slotTeam.append(0)

	slotUserID = []
	for i in range(0,16):
		slotUserID.append(-1)

	hostUserID = 999
	gameMode = 0
	matchScoringType = 0
	matchTeamType = 0
	matchModMode = 0
	seed = 50


	data = packetHelper.buildPacket(packetIDs.server_matchNew,[
		[matchID, dataTypes.uInt16],
		[0, dataTypes.byte],
		[matchType, dataTypes.byte],
		[mods, dataTypes.uInt32],
		[matchName, dataTypes.string],
		["", dataTypes.string],
		[beatmapName, dataTypes.string],
		[beatmapID, dataTypes.uInt32],
		[beatmapMD5, dataTypes.string],

		# Slot status
		[4, dataTypes.byte],
		[1, dataTypes.byte],
		[1, dataTypes.byte],
		[1, dataTypes.byte],
		[1, dataTypes.byte],
		[1, dataTypes.byte],
		[1, dataTypes.byte],
		[1, dataTypes.byte],
		[1, dataTypes.byte],
		[1, dataTypes.byte],
		[1, dataTypes.byte],
		[1, dataTypes.byte],
		[1, dataTypes.byte],
		[1, dataTypes.byte],
		[1, dataTypes.byte],
		[1, dataTypes.byte],

		# Slot team
		[0, dataTypes.byte],
		[0, dataTypes.byte],
		[0, dataTypes.byte],
		[0, dataTypes.byte],
		[0, dataTypes.byte],
		[0, dataTypes.byte],
		[0, dataTypes.byte],
		[0, dataTypes.byte],
		[0, dataTypes.byte],
		[0, dataTypes.byte],
		[0, dataTypes.byte],
		[0, dataTypes.byte],
		[0, dataTypes.byte],
		[0, dataTypes.byte],
		[0, dataTypes.byte],
		[0, dataTypes.byte],

		# Slot user ID
		[hostUserID, dataTypes.sInt32],
		#[0, dataTypes.sInt32],
		#[0, dataTypes.sInt32],
		#[0, dataTypes.sInt32],
		#[0, dataTypes.sInt32],
		#[0, dataTypes.sInt32],
		#[0, dataTypes.sInt32],
		#[0, dataTypes.sInt32],
		#[0, dataTypes.sInt32],
		#[0, dataTypes.sInt32],
		#[0, dataTypes.sInt32],
		#[0, dataTypes.sInt32],
		#[0, dataTypes.sInt32],
		#[0, dataTypes.sInt32],
		#[0, dataTypes.sInt32],
		#[0, dataTypes.sInt32],

		[999, dataTypes.uInt32],
		[gameMode, dataTypes.byte],
		[matchScoringType, dataTypes.byte],
		[matchTeamType, dataTypes.byte],
		[matchModMode, dataTypes.byte],

		[seed, dataTypes.sInt32]
	])
	return data


""" Other packets """
def notification(message):
	return packetHelper.buildPacket(packetIDs.server_notification, [[message, dataTypes.string]])

def jumpscare(message):
	return packetHelper.buildPacket(packetIDs.server_jumpscare, [[message, dataTypes.string]])

def banchoRestart(msUntilReconnection):
	return packetHelper.buildPacket(packetIDs.server_restart, [[msUntilReconnection, dataTypes.uInt32]])


""" WIP Packets """
def getAttention():
	return packetHelper.buildPacket(packetIDs.server_getAttention)

def packet80():
	return packetHelper.buildPacket(packetIDs.server_topBotnet)
