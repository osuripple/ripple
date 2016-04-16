import consoleHelper
import bcolors
import clientPackets
import serverPackets
import exceptions
import glob
import userHelper

def handle(userToken, packetData):
	try:
		# Get usertoken data
		userID = userToken.userID
		username = userToken.username

		# Start spectating packet
		packetData = clientPackets.startSpectating(packetData)

		# Stop spectating old user if needed
		if userToken.spectating != 0:
			oldTargetToken = glob.tokens.getTokenFromUserID(userToken.spectating)
			oldTargetToken.enqueue(serverPackets.removeSpectator(userID))
			userToken.stopSpectating()

		# Start spectating new user
		userToken.startSpectating(packetData["userID"])

		# Get host token
		targetToken = glob.tokens.getTokenFromUserID(packetData["userID"])
		if targetToken == None:
			raise exceptions.tokenNotFoundException

		# Add us to host's spectators
		targetToken.addSpectator(userID)

		# Send spectator join packet to host
		targetToken.enqueue(serverPackets.addSpectator(userID))

		# Join #spectator channel
		userToken.enqueue(serverPackets.channelJoinSuccess(userID, "#spectator"))

		if len(targetToken.spectators) == 1:
			# First spectator, send #spectator join to host too
			targetToken.enqueue(serverPackets.channelJoinSuccess(userID, "#spectator"))

		# Console output
		consoleHelper.printColored("> {} are spectating {}".format(username, userHelper.getUsername(packetData["userID"])), bcolors.PINK)
		consoleHelper.printColored("> {}'s spectators: {}".format(str(packetData["userID"]), str(targetToken.spectators)), bcolors.BLUE)
	except exceptions.tokenNotFoundException:
		# Stop spectating if token not found
		consoleHelper.printColored("[!] Spectator start: token not found", bcolors.RED)
		userToken.stopSpectating()
