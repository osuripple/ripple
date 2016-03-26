import glob
import channelPartEvent
import consoleHelper
import bcolors

def handle(userToken, _):
	# Get usertoken data
	userID = userToken.userID
	username = userToken.username

	# Remove user from users in lobby
	glob.matches.lobbyUserPart(userID)

	# Part lobby channel
	channelPartEvent.partChannel(userToken, "#lobby", True)

	# Console output
	consoleHelper.printColored("> {} has left multiplayer lobby".format(username), bcolors.BLUE)
