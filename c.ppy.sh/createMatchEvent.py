import serverPackets
import clientPackets
import glob
import consoleHelper
import bcolors
import joinMatchEvent

def handle(userToken, packetData):
	# get usertoken data
	userID = userToken.userID

	# Read packet data
	packetData = clientPackets.createMatch(packetData)

	# Create a match object
	# TODO: Player number check
	matchID = glob.matches.createMatch(packetData["matchName"], packetData["matchPassword"], packetData["beatmapID"], packetData["beatmapName"], packetData["beatmapMD5"], packetData["gameMode"], userID)

	# Join that match
	joinMatchEvent.joinMatch(userToken, matchID, packetData["matchPassword"])

	# Send match create packet to everyone in lobby
	for i in glob.matches.usersInLobby:
		# Make sure this user is still connected
		token = glob.tokens.getTokenFromUserID(i)
		if (token != None):
			token.enqueue(serverPackets.createMatch(matchID))

	# Console output
	consoleHelper.printColored("> MPROOM{}: Room created!".format(matchID), bcolors.BLUE)
