import exceptions
import clientPackets
import glob
import fokabot
import consoleHelper
import bcolors
import serverPackets

def handle(userToken, packetData):
	"""
	Event called when someone sends a public message

	userToken -- request user token
	packetData -- request data bytes
	"""

	try:
		# Get uesrToken data
		userID = userToken.userID
		username = userToken.username
		userRank = userToken.rank

		# Public chat packet
		packetData = clientPackets.sendPublicMessage(packetData)

		# Receivers
		who = []

		# Check #spectator
		if (packetData["to"] == "#spectator"):
			# Spectator channel
			# Send this packet to every spectator and host
			if (userToken.spectating == 0):
				# We have sent to send a message to our #spectator channel
				targetToken = userToken
				who = targetToken.spectators[:]
				# No need to remove us because we are the host so we are not in spectators list
			else:
				# We have sent a message to someone else's #spectator
				targetToken = glob.tokens.getTokenFromUserID(userToken.spectating)
				who = targetToken.spectators[:]

				# Remove us
				if (userID in who):
					who.remove(userID)

				# Add host
				who.append(targetToken.userID)
		elif (packetData["to"] == "#multiplayer"):
			# Multiplayer Channel
			# Get match ID and match object
			matchID = userToken.matchID

			# Make sure we are in a match
			if (matchID == -1):
				return

			# Make sure the match exists
			if (matchID not in glob.matches.matches):
				return

			# The match exists, get object
			match = glob.matches.matches[matchID]

			# Create targets list
			who = []
			for i in range(0,16):
				uid = match.slots[i]["userID"]
				if (uid > -1 and uid != userID):
					who.append(uid)
		else:
			# Standard channel
			# Make sure the channel exists
			if (packetData["to"] not in glob.channels.channels):
				raise exceptions.channelUnknownException

			# Make sure the channel is not in moderated mode
			if (glob.channels.channels[packetData["to"]].moderated == True and userRank < 2):
				raise exceptions.channelModeratedException

			# Make sure we have write permissions
			if (glob.channels.channels[packetData["to"]].publicWrite == False and userRank < 2):
				raise exceptions.channelNoPermissionsException

			# Send this packet to everyone in that channel except us
			who = glob.channels.channels[packetData["to"]].getConnectedUsers()[:]
			if userID in who:
				who.remove(userID)


		# Send packet to required users
		glob.tokens.multipleEnqueue(serverPackets.sendMessage(username, packetData["to"], packetData["message"]), who, False)

		# Fokabot command check
		fokaMessage = fokabot.fokabotResponse(username, packetData["to"], packetData["message"])
		if (fokaMessage != False):
			who.append(userID)
			glob.tokens.multipleEnqueue(serverPackets.sendMessage("FokaBot", packetData["to"], fokaMessage), who, False)
			consoleHelper.printColored("> FokaBot@{}: {}".format(packetData["to"], str(fokaMessage.encode("UTF-8"))), bcolors.PINK)

		# Console output
		consoleHelper.printColored("> {}@{}: {}".format(username, packetData["to"], str(packetData["message"].encode("UTF-8"))), bcolors.PINK)
	except exceptions.channelModeratedException:
		consoleHelper.printColored("[!] {} tried to send a message to a channel that is in moderated mode ({})".format(username, packetData["to"]), bcolors.RED)
	except exceptions.channelUnknownException:
		consoleHelper.printColored("[!] {} tried to send a message to an unknown channel ({})".format(username, packetData["to"]), bcolors.RED)
	except exceptions.channelNoPermissionsException:
		consoleHelper.printColored("[!] {} tried to send a message to channel {}, but they have no write permissions".format(username, packetData["to"]), bcolors.RED)
