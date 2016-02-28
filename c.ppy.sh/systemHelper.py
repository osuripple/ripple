import glob
import serverPackets
import psutil
import os

def runningUnderUnix():
	return True if os.name == "posix" else False

def restartServer():
	glob.tokens.enqueueAll(serverPackets.notification("We are performing some maintenance. Bancho will be restarted in 1 minute. Thank you for your patience."))
	glob.tokens.enqueueAll(serverPackets.banchoRestart())

def getSystemInfo():
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
