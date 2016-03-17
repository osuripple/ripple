import userHelper
import clientPackets

def handle(userToken, packetData):
	# Friend add packet
	packetData = clientPackets.addRemoveFriend(packetData)
	userHelper.addFriend(userToken.userID, packetData["friendID"])

	# Console output
	print("> {} have added {} to their friends".format(userToken.username, str(packetData["friendID"])))
