import random
import exceptions
import consoleHelper
import bcolors
import userHelper
import glob

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
	if "!faq rules" in message:
		return "Please make sure to check (Ripple's rules)[http://ripple.moe/?p=23]."
	if "!faq swearing" in message:
		return "Please don't abuse swearing"
	if "!faq spam" in message:
		return "Please don't spam"
	if "!faq offend" in message:
		return "Please offend other players"
	if "!report" in message:
		return "Report command is not here yet :("

	# Admin commands
	if "!moderated" in message:
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
	else:
		return False
