"""FokaBot related functions"""
import userHelper
import glob
import actions
import serverPackets
import fokabotCommands

def connect():
	"""Add FokaBot to connected users and send userpanel/stats packet to everyone"""

	token = glob.tokens.addToken(999)
	token.actionID = actions.idle
	glob.tokens.enqueueAll(serverPackets.userPanel(999))
	glob.tokens.enqueueAll(serverPackets.userStats(999))

def disconnect():
	"""Remove FokaBot from connected users"""

	glob.tokens.deleteToken(glob.tokens.getTokenFromUserID(999))

def fokabotResponse(fro, chan, message):
	"""
	Check if a message has triggered fokabot (and return its response)

	fro -- sender username (for permissions stuff with admin commands)
	chan -- channel name
	message -- message

	return -- fokabot's response string or False
	"""

	for i in fokabotCommands.commands:
		# Loop though all commands
		if i["trigger"] in message:
			# message has triggered a command

			# Make sure the user has right permissions
			if i["minRank"] > 1:
				# Get rank from db only if minrank > 1, so we save some CPU
				if userHelper.getRankPrivileges(userHelper.getID(fro)) < i["minRank"]:
					return False

			# Check argument number
			message = message.split(" ")
			if i["syntax"] != "" and len(message) <= len(i["syntax"].split(" ")):
				return "Wrong syntax: {} {}".format(i["trigger"], i["syntax"])

			# Return response or execute callback
			if i["callback"] == None:
				return i["response"]
			else:
				return i["callback"](fro, chan, message[1:])

	# No commands triggered
	return False
