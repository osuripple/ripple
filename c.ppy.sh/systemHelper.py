import glob
import serverPackets
import psutil
import os
import sys

import consoleHelper
import bcolors
import threading
import signal

def runningUnderUnix():
	"""
	Get if the server is running under UNIX or NT

	return --- True if running under UNIX, otherwise False
	"""

	return True if os.name == "posix" else False


def scheduleShutdown(sendRestartTime, restart, message = ""):
	"""
	Schedule a server shutdown/restart

	sendRestartTime -- time (seconds) to wait before sending server restart packets to every client
	restart -- if True, server will restart. if False, server will shudown
	message -- if set, send that message to every client to warn about the shutdown/restart
	"""

	# Console output
	consoleHelper.printColored("[!] Pep.py will {} in {} seconds!".format("restart" if restart else "shutdown", sendRestartTime+20), bcolors.PINK)
	consoleHelper.printColored("[!] Sending server restart packets in {} seconds...".format(sendRestartTime), bcolors.PINK)

	# Send notification if set
	if message != "":
		glob.tokens.enqueueAll(serverPackets.notification(message))

	# Schedule server restart packet
	threading.Timer(sendRestartTime, glob.tokens.enqueueAll, [serverPackets.banchoRestart(50000)]).start()
	glob.restarting = True

	# Restart/shutdown
	if restart:
		action = restartServer
	else:
		action = shutdownServer

	# Schedule actual server shutdown/restart 20 seconds after server restart packet, so everyone gets it
	threading.Timer(sendRestartTime+20, action).start()


def restartServer():
	"""Restart pep.py script"""
	print("> Restarting pep.py...")
	os.execv(sys.executable, [sys.executable] + sys.argv)


def shutdownServer():
	"""Shutdown pep.py"""
	print("> Shutting down pep.py...")
	sig = signal.SIGKILL if runningUnderUnix() else signal.CTRL_C_EVENT
	os.kill(os.getpid(), sig)


def getSystemInfo():
	"""
	Get a dictionary with some system/server info

	return -- ["unix", "connectedUsers", "webServer", "cpuUsage", "totalMemory", "usedMemory", "loadAverage"]
	"""

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
	if data["unix"] == True:
		data["loadAverage"] = os.getloadavg()
	else:
		data["loadAverage"] = (0,0,0)

	return data
