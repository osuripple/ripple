import random
import exceptions
import consoleHelper
import bcolors
import userHelper
import glob
import systemHelper

'''JUST A TEMPORARY MEME'''
def fokabotResponse(fro, chan, message):
	if "!roll" in message:
		maxPoints = 100
		message = message.split(" ")

		# Get max number if needed
		if (len(message) >= 2):
			if (message[1].isdigit() == True):
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
					systemHelper.restartServer()
					return False
				if (message[1] == "status"):
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
