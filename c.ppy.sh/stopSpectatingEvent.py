import consoleHelper
import bcolors
import glob
import serverPackets
import exceptions

def handle(userToken, _):
	try:
		# get user token data
		userID = userToken.userID
		username = userToken.username

		# Remove our userID from host's spectators
		target = userToken.spectating
		targetToken = glob.tokens.getTokenFromUserID(target)
		if targetToken == None:
			raise exceptions.tokenNotFoundException
		targetToken.removeSpectator(userID)

		# Send the spectator left packet to host
		targetToken.enqueue(serverPackets.removeSpectator(userID))

		# Console output
		# TODO: Move messages in stop spectating
		consoleHelper.printColored("> {} are no longer spectating whoever they were spectating".format(username), bcolors.PINK)
		consoleHelper.printColored("> {}'s spectators: {}".format(str(target), str(targetToken.spectators)), bcolors.BLUE)
	except exceptions.tokenNotFoundException:
		consoleHelper.printColored("[!] Spectator stop: token not found", bcolors.RED)
	finally:
		# Set our spectating user to 0
		userToken.stopSpectating()
