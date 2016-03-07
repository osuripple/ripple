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
	"""Add FokaBot to connected users"""

	token = glob.tokens.addToken(999)
	token.actionID = actions.idle


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
		return fro+" rolls "+str(points)+" points!"
	elif "!faq rules" in message:
		return "Please make sure to check (Ripple's rules)[http://ripple.moe/?p=23]."
	elif "!faq swearing" in message:
		return "Please don't abuse swearing"
	elif "!faq spam" in message:
		return "Please don't spam"
	elif "!faq offend" in message:
		return "Please offend other players"
	elif "!help" in message:
		return "Click (here)[https://ripple.moe/index.php?p=16&id=4] for full FokaBot's command list"
	elif "!report" in message:
		return "Report command is not here yet :("

	# Admin commands
	elif "!moderated" in message:
		try:
			# Admin check
			if (userHelper.getUserRank(userHelper.getUserID(fro)) <= 1):
				raise exceptions.noAdminException

			# Split message and default value
			message = message.lower().split(" ")
			enable = True

			# Get on/off
			if (len(message) >= 2):
				if (message[1] == "off"):
					enable = False

			# Turn on/off moderated mode
			glob.channels.channels[chan].moderated = enable

			return "This channel is now in moderated mode!" if enable else "This channel is no longer in moderated mode!"
		except exceptions.noAdminException:
			consoleHelper.printColored("[!] "+fro+" has tried to put "+chan+" in moderated mode, but he is not an admin.", bcolors.RED)
			return False
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
					msg += "Webserver: "+data["webServer"]+"\n"
					msg += "\n"
					msg += "=== BANCHO STATS ===\n"
					msg += "Connected users: "+str(data["connectedUsers"])+"\n"
					msg += "\n"
					msg += "=== SYSTEM STATS ===\n"
					msg += "CPU: "+str(data["cpuUsage"])+"%\n"
					msg += "RAM: "+str(data["usedMemory"])+"GB/"+str(data["totalMemory"])+"GB\n"
					if (data["unix"] == True):
						msg += "Load average: "+str(data["loadAverage"][0])+"/"+str(data["loadAverage"][1])+"/"+str(data["loadAverage"][2])+"\n"

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
			consoleHelper.printColored("[!] "+fro+" has tried to run a system command, but he is not an admin.", bcolors.RED)
			return False
		except exceptions.commandSyntaxException:
			consoleHelper.printColored("[!] Fokabot command syntax error", bcolors.RED)
			return False
	else:
		return False
