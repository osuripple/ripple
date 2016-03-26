import clientPackets
import serverPackets
import glob
import consoleHelper
import bcolors
import exceptions

def handle(userToken, packetData):
	# read packet data
	packetData = clientPackets.joinMatch(packetData)

	# Get match from ID
	joinMatch(userToken, packetData["matchID"], packetData["password"])


def joinMatch(userToken, matchID, password):
	try:
		# TODO: leave other matches
		# TODO: Stop spectating

		# get usertoken data
		userID = userToken.userID
		username = userToken.username

		# Make sure the match exists
		if (matchID not in glob.matches.matches):
			raise exceptions.matchNotFoundException

		# Match exists, get object
		match = glob.matches.matches[matchID]

		# Check password
		# TODO: Admins can enter every match
		if (match.matchPassword != ""):
			if (match.matchPassword != password):
				raise exceptions.matchWrongPasswordException

		# Password is correct, join match
		result = match.userJoin(userID)

		# Check if we've joined the match successfully
		if (result == False):
			raise exceptions.matchJoinErrorException

		# Match joined, set matchID for usertoken
		userToken.joinMatch(matchID)

		# Send packets
		userToken.enqueue(serverPackets.matchJoinSuccess(matchID))
		userToken.enqueue(serverPackets.channelJoinSuccess(userID, "#multiplayer"))
		userToken.enqueue(serverPackets.sendMessage("FokaBot", "#multiplayer", "Hi {}, and welcome to ripple's multiplayer mode! This feature is still WIP and might have some issues. If you find any bugs, please report them (by clicking here)[https://ripple.moe/index.php?p=22].".format(username)))
	except exceptions.matchNotFoundException:
		userToken.enqueue(serverPackets.matchJoinFail())
		consoleHelper.printColored("[!] {} has tried to join a mp room, but it doesn't exist".format(userToken.username), bcolors.RED)
	except exceptions.matchWrongPasswordException:
		userToken.enqueue(serverPackets.matchJoinFail())
		consoleHelper.printColored("[!] {} has tried to join a mp room, but he typed the wrong password".format(userToken.username), bcolors.RED)
	except exceptions.matchJoinErrorException:
		userToken.enqueue(serverPackets.matchJoinFail())
		consoleHelper.printColored("[!] {} has tried to join a mp room, but an error has occured".format(userToken.username), bcolors.RED)
