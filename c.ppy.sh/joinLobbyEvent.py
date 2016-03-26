import serverPackets
import glob
import consoleHelper
import bcolors

def handle(userToken, _):
	# Get userToken data
	username = userToken.username
	userID = userToken.userID

	# Add user to users in lobby
	glob.matches.lobbyUserJoin(userID)

	# Send matches data
	for key, _ in glob.matches.matches.items():
		userToken.enqueue(serverPackets.createMatch(key))

	# Console output
	consoleHelper.printColored("> {} has joined multiplayer lobby".format(username), bcolors.BLUE)
