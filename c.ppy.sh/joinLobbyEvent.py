import serverPackets
import packetHelper

def handle(userToken, packetData):
	userToken.enqueue(serverPackets.matchNew())
