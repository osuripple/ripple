import userHelper
import clientPackets

def handle(userToken, packetData):
	# Friend remove packet
	packetData = clientPackets.addRemoveFriend(packetData)
	userHelper.removeFriend(userToken.userID, packetData["friendID"])

	# Console output
	print("> {} have removed {} from their friends".format(userToken.username, str(packetData["friendID"])))
