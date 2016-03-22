import clientPackets
import serverPackets
import glob
import consoleHelper
import bcolors

def handle(userToken, packetData):
	# read packet data
	packetData = clientPackets.joinMatch(packetData)

	# Get match from ID
	match = glob.matches.getMatchFromMatchID(packetData["matchID"])
	joinMatch(userToken, match)


def joinMatch(userToken, match):
	# get usertoken data
	username = userToken.username
	userID = userToken.userID
	print(str(match))

	# Make sure the match exists
	if (match == None):
		userToken.enqueue(serverPackets.matchJoinFail())
		consoleHelper.printColored("[!] {} has tried to join a mp room, but it doesn't exist".format(username), bcolors.RED)
		return

	# Match exists, join it
	match.userJoin(userID)
	userToken.joinMatch(match.matchID)

	# Send packet
	userToken.enqueue(serverPackets.matchJoinSuccess())

	# Console output
	consoleHelper.printColored("> MPROOM{}: {} joined the room".format(match.matchID, username), bcolors.BLUE)
