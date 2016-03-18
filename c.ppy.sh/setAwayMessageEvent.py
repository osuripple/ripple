import clientPackets
import serverPackets

def handle(userToken, packetData):
	# get token data
	username = userToken.username

	# Read packet data
	packetData = clientPackets.setAwayMessage(packetData)

	# Set token away message
	userToken.setAwayMessage(packetData["awayMessage"])

	# Send private message from fokabot
	if (packetData["awayMessage"] == ""):
		fokaMessage = "Your away message has been reset"
	else:
		fokaMessage = "Your away message is now: {}".format(packetData["awayMessage"])
	userToken.enqueue(serverPackets.sendMessage("FokaBot", username, fokaMessage))
	print("{} has changed their away message to: {}".format(username, packetData["awayMessage"]))
