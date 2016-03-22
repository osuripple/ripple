import serverPackets
import clientPackets
import glob
import consoleHelper
import bcolors
import joinMatchEvent
import packetIDs

def handle(userToken, packetData):
	# get usertoken data
	userID = userToken.userID

	# Read packet data
	packetData = clientPackets.createMatch(packetData)

	# Create a match object
	# TODO: Player number check
	match = glob.matches.newMatch(packetData["matchName"], packetData["matchPassword"], packetData["beatmapID"], packetData["beatmapName"], packetData["beatmapMD5"], packetData["gameMode"], packetData["seed"])

	# Set host and join match
	match.setHost(userID)
	joinMatchEvent.joinMatch(userToken, match)

	# Send match create packet to everyone in lobby
	for i in glob.matches.usersInLobby:
		# Make sure this user is still connected
		token = glob.tokens.getTokenFromUserID(i)
		if (token != None):
			token.enqueue(serverPackets.matchSettings(match.matchID, False))

	# Console output
	consoleHelper.printColored("> MPROOM{}: Room created!".format(match.matchID), bcolors.BLUE)
