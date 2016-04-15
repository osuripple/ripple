import requests
from lets import glob
from helpers import generalHelper

def sendDiscordMessage(channel, message):
	"""
	Send a message to a discord server.
	This is used with ripple's schiavobot.

	channel -- bunk, staff or general
	message -- message to send
	"""
	if generalHelper.stringToBool(glob.conf.config["discord"]["enable"]) == True:
		requests.get("{}/{}?message={}".format(glob.conf.config["discord"]["boturl"], channel, message))


def sendConfidential(message):
	"""
	Send a message to #bunker

	message -- message to send
	"""
	sendDiscordMessage("bunk", message)


def sendStaff(message):
	"""
	Send a message to #staff

	message -- message to send
	"""
	sendDiscordMessage("staff", message)


def sendGeneral(message):
	"""
	Send a message to #general

	message -- message to send
	"""
	sendDiscordMessage("general", message)
