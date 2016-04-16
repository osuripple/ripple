import glob
import consoleHelper
import bcolors
import serverPackets
import exceptions

def handle(userToken, packetData):
	# get token data
	userID = userToken.userID

	# Send spectator frames to every spectator
	consoleHelper.printColored("> {}'s spectators: {}".format(str(userID), str(userToken.spectators)), bcolors.BLUE)
	for i in userToken.spectators:
		# Send to every user but host
		if i != userID:
			try:
				# Get spectator token object
				spectatorToken = glob.tokens.getTokenFromUserID(i)

				# Make sure the token exists
				if spectatorToken == None:
					raise exceptions.stopSpectating

				# Make sure this user is spectating us
				if spectatorToken.spectating != userID:
					raise exceptions.stopSpectating

				# Everything seems fine, send spectator frames to this spectator
				spectatorToken.enqueue(serverPackets.spectatorFrames(packetData[7:]))
			except exceptions.stopSpectating:
				# Remove this user from spectators
				userToken.removeSpectator(i)
				userToken.enqueue(serverPackets.removeSpectator(i))
