import consoleHelper
import bcolors
import glob

def handle(userToken, packetData):
	# get data from usertoken
	username = userToken.username
	userID = userToken.userID

	# Get match ID and match object
	matchID = userToken.matchID
	match = glob.matches.getMatchFromMatchID(matchID)

	# Make sure the match exists
	if (match == None):
		return

	# Set slot to free
	match.userLeft(userID)

	# Console output
	consoleHelper.printColored("> MPROOM{}: {} left the room".format(matchID, username), bcolors.BLUE)
