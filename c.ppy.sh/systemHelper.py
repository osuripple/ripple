import glob
import serverPackets
import psutil
import os
import sys

def runningUnderUnix():
	'''Get if the server is running under UNIX or NT

	return --- True if running under UNIX, otherwise False'''
	return True if os.name == "posix" else False

def restartServer():
	'''Restart pep.py script'''
	print("> Restarting pep.py...")
	os.execv(sys.executable, [sys.executable] + sys.argv)

def getSystemInfo():
	'''Get a dictionary with some system/server info

	return -- ["unix", "connectedUsers", "webServer", "cpuUsage", "totalMemory", "usedMemory", "loadAverage"]'''
	data = {}

	# Get if server is running under unix/nt
	data["unix"] = runningUnderUnix()

	# General stats
	data["connectedUsers"] = len(glob.tokens.tokens)
	data["webServer"] = glob.conf.config["server"]["server"]
	data["cpuUsage"] = psutil.cpu_percent()
	data["totalMemory"] = "{0:.2f}".format(psutil.virtual_memory()[0]/1074000000)
	data["usedMemory"] = "{0:.2f}".format(psutil.virtual_memory()[3]/1074000000)

	# Unix only stats
	if (data["unix"] == True):
		data["loadAverage"] = os.getloadavg()
	else:
		data["loadAverage"] = (0,0,0)

	return data
