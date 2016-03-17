import glob
import consoleHelper
import bcolors
import serverPackets
import time

def handle(userToken, _):
	# get usertoken data
	userID = userToken.userID
	username = userToken.username
	requestToken = userToken.token

	# Big client meme here. If someone logs out and logs in right after,
	# the old logout packet will still be in the queue and will be sent to
	# the server, so we accept logout packets sent at least 5 seconds after login
	if (int(time.time()-userToken.loginTime) >= 5):
		# TODO: Channel part at logout
		# TODO: Stop spectating at logout
		# TODO: Stop spectating at timeout
		# Enqueue our disconnection to everyone else
		glob.tokens.enqueueAll(serverPackets.userLogout(userID))

		# Delete token
		glob.tokens.deleteToken(requestToken)

		consoleHelper.printColored("> {} have been disconnected (logout)".format(username), bcolors.YELLOW)
