"""FokaBot related functions"""

import random
import exceptions
import consoleHelper
import bcolors
import userHelper
import glob
import systemHelper
import actions
import serverPackets

import time
import threading

def connect():
	"""Add FokaBot to connected users and send userpanel/stats packet to everyone"""

	token = glob.tokens.addToken(999)
	token.actionID = actions.idle
	glob.tokens.enqueueAll(serverPackets.userPanel(999))
	glob.tokens.enqueueAll(serverPackets.userStats(999))


def disconnect():
	"""Remove FokaBot from connected users"""

	glob.tokens.deleteToken(getTokenFromUserID(999))


def fokabotResponse(fro, chan, message):
	"""
	Check if a message has triggered fokabot (and return its response)

	fro -- sender username (for permissions stuff with admin commands)
	chan -- channel name
	message -- message

	return -- fokabot's response string or False
	"""

	if "!roll" in message:
		maxPoints = 100
		message = message.split(" ")

		# Get max number if needed
		if (len(message) >= 2):
			if (message[1].isdigit() == True and int(message[1]) > 0):
				maxPoints = int(message[1])

		points = random.randrange(0,maxPoints)
		return "{} rolls {} points!".format(fro, str(points))
	elif "!faq rules" in message:
		return "Please make sure to check (Ripple's rules)[http://ripple.moe/?p=23]."
	elif "!faq swearing" in message:
		return "Please don't abuse swearing"
	elif "!faq spam" in message:
		return "Please don't spam"
	elif "!faq offend" in message:
		return "Please don't offend other players"
	elif "!help" in message:
		return "Click (here)[https://ripple.moe/index.php?p=16&id=4] for FokaBot's full command list"
	elif "!report" in message:
		return "Report command is not here, yet :("

	# Admin commands
	elif "!moderated" in message:
		try:
			# Admin check
			if (userHelper.getUserRank(userHelper.getUserID(fro)) <= 1):
				raise exceptions.noAdminException

			# Make sure we are in a channel and not PM
			if (chan.startswith("#") == False):
				raise exceptions.moderatedPMException

			# Split message and default value
			message = message.lower().split(" ")
			enable = True

			# Get on/off
			if (len(message) >= 2):
				if (message[1] == "off"):
					enable = False

			# Turn on/off moderated mode
			glob.channels.channels[chan].moderated = enable

			return "This channel is {} in moderated mode!".format("now" if enable else "no longer")
		except exceptions.noAdminException:
			consoleHelper.printColored("[!] {} tried to put {} in moderated mode, but they are not an admin.".format(fro, chan), bcolors.RED)
			return False
		except exceptions.moderatedPMException:
			consoleHelper.printColored("[!] {} tried to put a PM chat in moderated mode.".format(fro), bcolors.RED)
			return "You are trying to put a private chat in moderated mode. Are you serious?!? You're fired."
	elif "!system" in message:
		# System commands
		try:
			# Admin check
			if (userHelper.getUserRank(userHelper.getUserID(fro)) <= 1):
				raise exceptions.noAdminException

			# Split message
			message = message.lower().split(" ")

			# Get parameters
			if (len(message) >= 2):
				if (message[1] == "restart"):
					msg = "We are performing some maintenance. Bancho will restart in 5 seconds. Thank you for your patience."
					systemHelper.scheduleShutdown(5, True, msg)
					return msg
				elif (message[1] == "status"):
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
				elif (message[1] == "reload"):
					#Reload settings from bancho_settings
					glob.banchoConf.loadSettings()
					return "Bancho settings reloaded!"
				elif (message[1] == "maintenance"):
					# Turn on/off bancho maintenance
					maintenance = True

					# Get on/off
					if (len(message) >= 2):
						if (message[2] == "off"):
							maintenance = False

					# Set new maintenance value in bancho_settings table
					glob.banchoConf.setMaintenance(maintenance)

					if (maintenance == True):
						# We have turned on maintenance mode
						# Users that will be disconnected
						who = []

						# Disconnect everyone but mod/admins
						for key,value in glob.tokens.tokens.items():
							if (value.rank <= 2):
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
			else:
				raise exceptions.commandSyntaxException

		except exceptions.noAdminException:
			consoleHelper.printColored("[!] {} tried to run a system command, but they are not an admin.".format(fro), bcolors.RED)
			return False
		except exceptions.commandSyntaxException:
			consoleHelper.printColored("[!] Fokabot command syntax error", bcolors.RED)
			return False
	elif "!scareall" in message:
		try:
			# Admin check
			if (userHelper.getUserRank(userHelper.getUserID(fro)) <= 1):
				raise exceptions.noAdminException

			# Get parameters
			message = message.lower().split(" ")
			if (len(message) < 2):
				raise exceptions.commandSyntaxException
			scaryMessage = ' '.join(message[1:])

			# Send packet to everyone
			consoleHelper.printColored("> {} is turning osu! into an horror game ({})".format(fro, scaryMessage), bcolors.PINK)
			glob.tokens.enqueueAll(serverPackets.jumpscare(scaryMessage))

		except exceptions.noAdminException:
			pass
		except exceptions.commandSyntaxException:
			return "Wrong syntax. !scareall <message>"
		finally:
			# No respobnse
			return False
	elif "!scare" in message:
		try:
			# Admin check
			if (userHelper.getUserRank(userHelper.getUserID(fro)) <= 1):
				raise exceptions.noAdminException

			# Get parameters
			message = message.lower().split(" ")
			if (len(message) < 3):
				raise exceptions.commandSyntaxException
			target = message[1]
			scaryMessage = ' '.join(message[2:])

			# Get target token and make sure is connected
			targetToken = glob.tokens.getTokenFromUsername(target)
			if (targetToken == None):
				raise exceptions.tokenNotFoundException

			# Send packet to target
			consoleHelper.printColored("> Rip {}'s heart ({}). ~ <3, {}".format(target, scaryMessage, fro), bcolors.PINK)
			targetToken.enqueue(serverPackets.jumpscare(scaryMessage))

			# No response
			return False
		except exceptions.noAdminException:
			return False
		except exceptions.tokenNotFoundException:
			return "{} is not online".format(message[1])
		except exceptions.commandSyntaxException:
			return "Wrong syntax. !scare <target> <message>"
	elif "!kick" in message:
		try:
			# Admin check
			# TODO: God this sucks
			if (userHelper.getUserRank(userHelper.getUserID(fro)) <= 1):
				raise exceptions.noAdminException

			# Get parameters
			message = message.lower().split(" ")
			if (len(message) < 2):
				raise exceptions.commandSyntaxException
			target = message[1]

			# Get target token and make sure is connected
			targetToken = glob.tokens.getTokenFromUsername(target)
			if (targetToken == None):
				raise exceptions.tokenNotFoundException

			# Send packet to target
			consoleHelper.printColored("> {} has been disconnected. (kick)".format(target), bcolors.YELLOW)
			targetToken.enqueue(serverPackets.notification("You have been kicked from the server. Please login again."))
			targetToken.enqueue(serverPackets.loginFailed())

			# Bot response
			return "{} has been kicked from the server.".format(message[1])
		except exceptions.noAdminException:
			return False
		except exceptions.tokenNotFoundException:
			return "{} is not online.".format(message[1])
		except exceptions.commandSyntaxException:
			return "Wrong syntax. !kick <target>"
	elif "!silence" in message:
		try:
			# Admin check
			if (userHelper.getUserRank(userHelper.getUserID(fro)) <= 1):
				raise exceptions.noAdminException

			# Get parameters
			message = message.lower().split(" ")
			if (len(message) < 4):
				raise exceptions.commandSyntaxException
			target = message[1]
			amount = message[2]
			unit = message[3]
			reason = ' '.join(message[4:])

			# Get target user ID
			targetUserID = userHelper.getUserID(target)

			# Make sure the user exists
			if (targetUserID == False):
				raise exceptions.userNotFoundException

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
				raise exceptions.commandSyntaxException

			# Max silence time is 7 days
			if (silenceTime > 604800):
				raise exceptions.commandSyntaxException

			# Calculate silence end time
			endTime = int(time.time())+silenceTime

			# Update silence end in db
			userHelper.silenceUser(targetUserID, endTime, reason)

			# Check if target is connected
			targetToken = glob.tokens.getTokenFromUsername(target)
			if (targetToken == None):
				tokenFound = False
			else:
				tokenFound = True

			# Send silence packets if user is online
			if (tokenFound == True):
				targetToken.enqueue(serverPackets.silenceEndTime(silenceTime))

			consoleHelper.printColored("{} has been silenced for {} seconds the following reason: {}".format(target, silenceTime, reason), bcolors.PINK)

			# Bot response
			return "{} has been silenced for the following reason: {}".format(target, reason)
		except exceptions.userNotFoundException:
			return "{}: user not found".format(message[1])
		except exceptions.noAdminException:
			return False
		except exceptions.commandSyntaxException:
			return "Wrong syntax. !silence <target> <amount> <unit (s/m/h/d)> <reason>. Max silence time is 7 days."
	elif "!removesilence" in message or "!resetsilence" in message:
		try:
			# Admin check
			if (userHelper.getUserRank(userHelper.getUserID(fro)) <= 1):
				raise exceptions.noAdminException

			# Get parameters
			message = message.lower().split(" ")
			if (len(message) < 2):
				raise exceptions.commandSyntaxException
			target = message[1]

			# Make sure the user exists
			targetUserID = userHelper.getUserID(target)
			if (targetUserID == False):
				raise exceptions.userNotFoundException

			# Reset user silence time and reason in db
			userHelper.silenceUser(targetUserID, 0, "")

			# Send new silence end packet to user if he's online
			targetToken = glob.tokens.getTokenFromUsername(target)
			if (targetToken != None):
				targetToken.enqueue(serverPackets.silenceEndTime(0))

			return "{}'s silence reset".format(target)
		except exceptions.commandSyntaxException:
			return "Wrong syntax. !removesilence <target>"
		except exceptions.noAdminException:
			return False
		except exceptions.userNotFoundException:
			return "{}: user not found".format(message[1])
	elif "!fokabot reconnect" in message:
		try:
			# Admin check
			if (userHelper.getUserRank(userHelper.getUserID(fro)) <= 1):
				raise exceptions.noAdminException

			# Check if fokabot is already connected
			if (glob.tokens.getTokenFromUserID(999) != None):
				raise exceptions.alreadyConnectedException

			# Fokabot is not connected, connect it
			connect()
			return False
		except exceptions.noAdminException:
			return False
		except exceptions.alreadyConnectedException:
			return "Fokabot is already connected to Bancho"
	else:
		return False
