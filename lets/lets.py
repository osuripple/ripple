# General imports
import sys
import os
import glob
from helpers import consoleHelper
from helpers import databaseHelper
from helpers import config
from constants import bcolors

# Handlers
from handlers import getScoresHandler
from handlers import submitModularHandler
from handlers import banchoConnectHandler
from handlers import getReplayHandler

# Tornado
import tornado.ioloop
import tornado.web

def make_app():
	return tornado.web.Application([
		(r"/web/bancho_connect.php", banchoConnectHandler.handler),
		(r"/web/osu-osz2-getscores.php", getScoresHandler.handler),
		(r"/web/osu-submit-modular.php", submitModularHandler.handler),
		(r"/web/osu-getreplay.php", getReplayHandler.handler)
	])

if __name__ == "__main__":
	consoleHelper.printServerStartHeader(True)

	# Read config
	consoleHelper.printNoNl("> Reading config file... ")
	glob.conf = config.config("config.ini")

	if (glob.conf.default == True):
		# We have generated a default config.ini, quit server
		consoleHelper.printWarning()
		consoleHelper.printColored("[!] config.ini not found. A default one has been generated.", bcolors.YELLOW)
		consoleHelper.printColored("[!] Please edit your config.ini and run the server again.", bcolors.YELLOW)
		sys.exit()

	# If we haven't generated a default config.ini, check if it's valid
	if (glob.conf.checkConfig() == False):
		consoleHelper.printError()
		consoleHelper.printColored("[!] Invalid config.ini. Please configure it properly", bcolors.RED)
		consoleHelper.printColored("[!] Delete your config.ini to generate a default one", bcolors.RED)
		sys.exit()
	else:
		consoleHelper.printDone()

	# Connect to db
	try:
		consoleHelper.printNoNl("> Connecting to MySQL db... ")
		glob.db = databaseHelper.db(glob.conf.config["db"]["host"], glob.conf.config["db"]["username"], glob.conf.config["db"]["password"], glob.conf.config["db"]["database"], int(glob.conf.config["db"]["pingtime"]))
		consoleHelper.printDone()
	except:
		# Exception while connecting to db
		consoleHelper.printError()
		consoleHelper.printColored("[!] Error while connection to database. Please check your config.ini and run the server again", bcolors.RED)
		raise

	# Create data folder if needed
	consoleHelper.printNoNl("> Checking folders... ")
	paths = [".data", ".data/replays"]
	for i in paths:
		if not os.path.exists(i):
			os.makedirs(i, 0o770)
	consoleHelper.printDone()

	# Enable PP if this is the officialTM ripple server
	if os.path.isfile("ripp.py"):
		glob.pp = True
		import ripp
		print("> Using {}ripp v{}{} as PP calculator.".format(bcolors.GREEN, ripp.VERSION, bcolors.ENDC))
	else:
		consoleHelper.printColored("[!] No PP calculator found. PP are disabled.", bcolors.YELLOW)

	# Start the server
	serverPort = int(glob.conf.config["server"]["port"])
	consoleHelper.printColored("> Starting L.E.T.S. on 127.0.0.1:{}...".format(serverPort), bcolors.GREEN)
	app = make_app()
	app.listen(serverPort)
	tornado.ioloop.IOLoop.current().start()
