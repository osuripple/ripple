import serverPackets
import glob
import consoleHelper
import bcolors
import packetIDs

def handle(userToken, packetData):
	# Get userToken data
	username = userToken.username
	userID = userToken.userID

	# Add user to users in lobby
	glob.matches.lobbyUserJoin(userID)

	# Send matches data
	for i in glob.matches.matches:
		userToken.enqueue(serverPackets.matchSettings(i.matchID, False))

	# Console output
	consoleHelper.printColored("> {} has joined multiplayer lobby".format(username), bcolors.BLUE)
