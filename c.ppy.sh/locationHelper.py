import urllib.request
import json

import consoleHelper
import bcolors

# API URL
url = "http://ip.zxq.co/"


def getCountry(ip):
	"""Get country from IP address

	ip -- IP Address
	return -- Country code (2 letters)"""

	# Default value, sent if API is memeing
	country = "XX"

	try:
		# Try to get country from Pikolo Aul's Go-Sanic ip API
		country = json.loads(urllib.request.urlopen(url+"/"+ip).read().decode())["country"]
	except:
		consoleHelper.printColored("[!] Error in get country", bcolors.RED)

	return country


def getLocation(ip):
	"""Get latitude and longitude from IP address

	ip -- IP address
	return -- [latitude, longitude]"""

	# Default value, sent if API is memeing
	data = [0,0]

	try:
		# Try to get position from Pikolo Aul's Go-Sanic ip API
		data = json.loads(urllib.request.urlopen(url+"/"+ip).read().decode())["loc"].split(",")
	except:
		consoleHelper.printColored("[!] Error in get position", bcolors.RED)

	return [float(data[0]), float(data[1])]
