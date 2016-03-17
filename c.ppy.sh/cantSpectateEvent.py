import glob
import serverPackets
import consoleHelper
import bcolors
import exceptions

def handle(userToken, packetData):
	# get usertoken data
	userID = userToken.userID

	try:
		# We don't have the beatmap, we can't spectate
		target = userToken.spectating
		targetToken = glob.tokens.getTokenFromUserID(target)

		# Send the packet to host
		targetToken.enqueue(serverPackets.noSongSpectator(userID))
	except exceptions.tokenNotFoundException:
		# Stop spectating if token not found
		consoleHelper.printColored("[!] Spectator can't spectate: token not found", bcolors.RED)
		userToken.stopSpectating()
