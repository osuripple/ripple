import glob
import consoleHelper
import bcolors
import serverPackets

def handle(userToken, packetData):
	# get token data
	userID = userToken.userID

	# Send spectator frames to every spectator
	consoleHelper.printColored("> {}'s spectators: {}".format(str(userID), str(userToken.spectators)), bcolors.BLUE)
	for i in userToken.spectators:
		if (i != userID):
			# TODO: Check that spectators are spectating us
			# Send to every spectator but us (host)
			spectatorToken = glob.tokens.getTokenFromUserID(i)
			if (spectatorToken != None):
				# Token found, send frames
				spectatorToken.enqueue(serverPackets.spectatorFrames(packetData[7:]))
			else:
				# Token not found, remove it
				userToken.removeSpectator(i)
				userToken.enqueue(serverPackets.removeSpectator(i))
