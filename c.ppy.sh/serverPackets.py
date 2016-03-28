""" Contains functions used to write specific server packets to byte streams """
import packetHelper
import dataTypes
import userHelper
import glob
import userRanks
import packetIDs
import slotStatuses
import matchModModes

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

def channelKicked(chan):
	return packetHelper.buildPacket(packetIDs.server_channelKicked, [[chan, dataTypes.string]])


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
def createMatch(matchID):
	# Make sure the match exists
	if (matchID not in glob.matches.matches):
		return None

	# Get match binary data and build packet
	match = glob.matches.matches[matchID]
	return packetHelper.buildPacket(packetIDs.server_newMatch, match.getMatchData())


def updateMatch(matchID):
	# Make sure the match exists
	if (matchID not in glob.matches.matches):
		return None

	# Get match binary data and build packet
	match = glob.matches.matches[matchID]
	return packetHelper.buildPacket(packetIDs.server_updateMatch, match.getMatchData())


def matchStart(matchID):
	# Make sure the match exists
	if (matchID not in glob.matches.matches):
		return None

	# Get match binary data and build packet
	match = glob.matches.matches[matchID]
	return packetHelper.buildPacket(packetIDs.server_matchStart, match.getMatchData())


def disposeMatch(matchID):
	return packetHelper.buildPacket(packetIDs.server_disposeMatch, [[matchID, dataTypes.uInt16]])

def matchJoinSuccess(matchID):
	# Make sure the match exists
	if (matchID not in glob.matches.matches):
		return None

	# Get match binary data and build packet
	match = glob.matches.matches[matchID]
	data = packetHelper.buildPacket(packetIDs.server_matchJoinSuccess, match.getMatchData())
	return data

def matchJoinFail():
	return packetHelper.buildPacket(packetIDs.server_matchJoinFail)

def changeMatchPassword(newPassword):
	return packetHelper.buildPacket(packetIDs.server_matchChangePassword, [[newPassword, dataTypes.string]])

def allPlayersLoaded():
	return packetHelper.buildPacket(packetIDs.server_matchAllPlayersLoaded)

def playerSkipped(userID):
	return packetHelper.buildPacket(packetIDs.server_matchPlayerSkipped, [[userID, dataTypes.sInt32]])

def allPlayersSkipped():
	return packetHelper.buildPacket(packetIDs.server_matchSkip)

def matchFrames(slotID, data):
	return packetHelper.buildPacket(packetIDs.server_matchScoreUpdate, [[data[7:11], dataTypes.bbytes], [slotID, dataTypes.byte], [data[12:], dataTypes.bbytes]])

def matchComplete():
	return packetHelper.buildPacket(packetIDs.server_matchComplete)

def playerFailed(slotID):
	return packetHelper.buildPacket(packetIDs.server_matchPlayerFailed, [[slotID, dataTypes.uInt32]])

def matchTransferHost():
	return packetHelper.buildPacket(packetIDs.server_matchTransferHost)

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
