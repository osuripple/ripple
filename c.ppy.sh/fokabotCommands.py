import fokabot
import random
import glob
import serverPackets
import exceptions
import userHelper
import time
import systemHelper

"""
Commands callbacks

Must have fro, chan and messages as arguments
fro -- name of who triggered the command
chan -- channel where the message was sent
message -- 	list containing arguments passed from the message
			[0] = first argument
			[1] = second argument
			. . .

return the message or **False** if there's no response by the bot
"""

def faq(fro, chan, message):
	if message[0] == "rules":
		return "Please make sure to check (Ripple's rules)[http://ripple.moe/?p=23]."
	elif message[0] == "rules":
		return "Please make sure to check (Ripple's rules)[http://ripple.moe/?p=23]."
	elif message[0] == "swearing":
		return "Please don't abuse swearing"
	elif message[0] == "spam":
		return "Please don't spam"
	elif message[0] == "offend":
		return "Please don't offend other players"
	elif message[0] == "github":
		return "(Ripple's Github page!)[https://github.com/osuripple/ripple]"
	elif message[0] == "discord":
		return "(Join Ripple's Discord!)[https://discord.gg/0rJcZruIsA6rXuIx]"
	elif message[0] == "blog":
		return "You can find the latest Ripple news on the (blog)[https://ripple.moe/blog/]!"
	elif message[0] == "changelog":
		return "Check the (changelog)[https://ripple.moe/index.php?p=17] !"
	elif message[0] == "status":
		return "Check the server status (here!)[https://ripple.moe/index.php?p=27]"

def roll(fro, chan, message):
	maxPoints = 100
	if len(message) >= 1:
		if message[0].isdigit() == True and int(message[0]) > 0:
			maxPoints = int(message[0])

	points = random.randrange(0,maxPoints)
	return "{} rolls {} points!".format(fro, str(points))

def ask(fro, chan, message):
	return random.choice(["yes", "no", "maybe"])

def alert(fro, chan, message):
	glob.tokens.enqueueAll(serverPackets.notification(' '.join(message[:])))
	return False

def moderated(fro, chan, message):
	try:
		# Make sure we are in a channel and not PM
		if (chan.startswith("#") == False):
			raise exceptions.moderatedPMException

		# Get on/off
		enable = True
		if len(message) >= 1:
			if message[0] == "off":
				enable = False

		# Turn on/off moderated mode
		glob.channels.channels[chan].moderated = enable
		return "This channel is {} in moderated mode!".format("now" if enable else "no longer")
	except exceptions.moderatedPMException:
		return "You are trying to put a private chat in moderated mode. Are you serious?!? You're fired."

def kickAll(fro, chan, message):
	# Kick everyone but mods/admins
	toKick = []
	for key, value in glob.tokens.tokens.items():
		if (value.rank < 3):
			toKick.append(key)

	# Loop though users to kick (we can't change dictionary size while iterating)
	for i in toKick:
		if (i in glob.tokens.tokens):
			glob.tokens.tokens[i].kick()

	return "Whoops! Rip everyone."

def kick(fro, chan, message):
	# Get parameters
	target = message[0].replace("_", " ")

	# Get target token and make sure is connected
	targetToken = glob.tokens.getTokenFromUsername(target)
	if (targetToken == None):
		return "{} is not online".format(target)

	# Kick user
	targetToken.kick()

	# Bot response
	return "{} has been kicked from the server.".format(target)

def fokabotReconnect(fro, chan, message):
	# Check if fokabot is already connected
	if (glob.tokens.getTokenFromUserID(999) != None):
		return"Fokabot is already connected to Bancho"

	# Fokabot is not connected, connect it
	fokabot.connect()
	return False

def silence(fro, chan, message):
	for i in message:
		i = i.lower()
	target = message[0].replace("_", " ")
	amount = message[1]
	unit = message[2]
	reason = ' '.join(message[3:])

	# Get target user ID
	targetUserID = userHelper.getID(target)

	# Make sure the user exists
	if (targetUserID == False):
		return "{}: user not found".format(target)

	# Calculate silence seconds
	if (unit == 's'):
		silenceTime = int(amount)
	elif (unit == 'm'):
		silenceTime = int(amount)*60
	elif (unit == 'h'):
		silenceTime = int(amount)*3600
	elif (unit == 'd'):
		silenceTime = int(amount)*86400
	else:
		return "Invalid time unit (s/m/h/d)."

	# Max silence time is 7 days
	if (silenceTime > 604800):
		return "Invalid silence time. Max silence time is 7 days."

	# Calculate silence end time
	endTime = int(time.time())+silenceTime

	# Update silence end in db
	userHelper.silence(targetUserID, endTime, reason)

	# Send silence packet to target if he's connected
	targetToken = glob.tokens.getTokenFromUsername(target)
	if (targetToken != None):
		targetToken.enqueue(serverPackets.silenceEndTime(silenceTime))

	return "{} has been silenced for the following reason: {}".format(target, reason)

def removeSilence(fro, chan, message):
	# Get parameters
	for i in message:
		i = i.lower()
	target = message[0].replace("_", " ")

	# Make sure the user exists
	targetUserID = userHelper.getID(target)
	if (targetUserID == False):
		return "{}: user not found".format(target)

	# Reset user silence time and reason in db
	userHelper.silence(targetUserID, 0, "")

	# Send new silence end packet to user if he's online
	targetToken = glob.tokens.getTokenFromUsername(target)
	if (targetToken != None):
		targetToken.enqueue(serverPackets.silenceEndTime(0))

	return "{}'s silence reset".format(target)

def restartShutdown(restart):
	"""Restart (if restart = True) or shutdown (if restart = False) pep.py safely"""
	msg = "We are performing some maintenance. Bancho will {} in 5 seconds. Thank you for your patience.".format("restart" if restart else "shutdown")
	systemHelper.scheduleShutdown(5, restart, msg)
	return msg

def systemRestart(fro, chan, message):
	return restartShutdown(True)

def systemShutdown(fro, chan, message):
	return restartShutdown(False)

def systemReload(fro, chan, message):
	#Reload settings from bancho_settings
	glob.banchoConf.loadSettings()

	# Reload channels too
	glob.channels.loadChannels()

	# Send new channels and new bottom icon to everyone
	glob.tokens.enqueueAll(serverPackets.mainMenuIcon(glob.banchoConf.config["menuIcon"]))
	glob.tokens.enqueueAll(serverPackets.channelInfoEnd())
	for key, _ in glob.channels.channels.items():
		glob.tokens.enqueueAll(serverPackets.channelInfo(key))

	return "Bancho settings reloaded!"

def systemMaintenance(fro, chan, message):
	# Turn on/off bancho maintenance
	maintenance = True

	# Get on/off
	if (len(message) >= 2):
		if (message[1] == "off"):
			maintenance = False

	# Set new maintenance value in bancho_settings table
	glob.banchoConf.setMaintenance(maintenance)

	if (maintenance == True):
		# We have turned on maintenance mode
		# Users that will be disconnected
		who = []

		# Disconnect everyone but mod/admins
		for _, value in glob.tokens.tokens.items():
			if (value.rank < 3):
				who.append(value.userID)

		glob.tokens.enqueueAll(serverPackets.notification("Our bancho server is in maintenance mode. Please try to login again later."))
		glob.tokens.multipleEnqueue(serverPackets.loginError(), who)
		msg = "The server is now in maintenance mode!"
	else:
		# We have turned off maintenance mode
		# Send message if we have turned off maintenance mode
		msg = "The server is no longer in maintenance mode!"

	# Chat output
	return msg

def systemStatus(fro, chan, message):
	# Print some server info
	data = systemHelper.getSystemInfo()

	# Final message
	msg =  "=== PEP.PY STATS ===\n"
	msg += "Running pep.py server\n"
	msg += "Webserver: {}\n".format(data["webServer"])
	msg += "\n"
	msg += "=== BANCHO STATS ===\n"
	msg += "Connected users: {}\n".format(str(data["connectedUsers"]))
	msg += "\n"
	msg += "=== SYSTEM STATS ===\n"
	msg += "CPU: {}%\n".format(str(data["cpuUsage"]))
	msg += "RAM: {}GB/{}GB\n".format(str(data["usedMemory"]), str(data["totalMemory"]))
	if (data["unix"] == True):
		msg += "Load average: {}/{}/{}\n".format(str(data["loadAverage"][0]), str(data["loadAverage"][1]), str(data["loadAverage"][2]))

	return msg

"""
Commands list

trigger: message that triggers the command
callback: function to call when the command is triggered. Optional.
response: text to return when the command is triggered. Optional.
syntax: command syntax. Arguments must be separated by spaces (eg: <arg1> <arg2>)
minRank: minimum rank to execute that command. Optional (default = 1)

You MUST set trigger and callback/response, or the command won't work.
"""
commands = [
	{
		"trigger": "!roll",
		"callback": roll
	}, {
		"trigger": "!faq",
		"syntax": "<name>",
		"callback": faq
	}, {
		"trigger": "!report",
		"response": "Report command isn't here yet :c"
	}, {
		"trigger": "!help",
		"response": "Click (here)[https://ripple.moe/index.php?p=16&id=4] for FokaBot's full command list"
	}, {
		"trigger": "!ask",
		"syntax": "<question>",
		"callback": ask
	}, {
		"trigger": "!mm00",
		"response": random.choice(["meme", "MA MAURO ESISTE?"])
	}, {
		"trigger": "!alert",
		"syntax": "<message>",
		"minRank": 4,
		"callback": alert
	}, {
		"trigger": "!moderated",
		"minRank": 3,
		"callback": moderated
	}, {
		"trigger": "!kickall",
		"minRank": 4,
		"callback": kickAll
	}, {
		"trigger": "!kick",
		"syntax": "<target>",
		"minRank": 3,
		"callback": kick
	}, {
		"trigger": "!fokabot reconnect",
		"minRank": 3,
		"callback": fokabotReconnect
	}, {
		"trigger": "!silence",
		"syntax": "<target> <amount> <unit(s/m/h/d)> <reason>",
		"minRank": 3,
		"callback": silence
	}, {
		"trigger": "!removesilence",
		"syntax": "<target>",
		"minRank": 3,
		"callback": removeSilence
	}, {
		"trigger": "!system restart",
		"minRank": 4,
		"callback": systemRestart
	}, {
		"trigger": "!system shutdown",
		"minRank": 4,
		"callback": systemShutdown
	}, {
		"trigger": "!system reload",
		"minRank": 3,
		"callback": systemReload
	}, {
		"trigger": "!system maintenance",
		"minRank": 3,
		"callback": systemMaintenance
	}, {
		"trigger": "!system status",
		"minRank": 3,
		"callback": systemStatus
	}
]

# Commands list default values
for cmd in commands:
	cmd.setdefault("syntax", "")
	cmd.setdefault("minRank", 1)
	cmd.setdefault("callback", None)
	cmd.setdefault("response", "u w0t m8?")
