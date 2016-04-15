"""Some console related functions"""

from constants import bcolors
from lets import glob

def printServerStartHeader(asciiArt):
	"""
	Print server start header with optional ascii art

	asciiArt -- if True, will print ascii art too
	"""

	if (asciiArt == True):
		printColored(" (                 (     ", bcolors.YELLOW)
		printColored(" )\\ )        *   ) )\\ )  ", bcolors.YELLOW)
		printColored("(()/(  (   ` )  /((()/(  ", bcolors.YELLOW)
		printColored(" /(_)) )\\   ( )(_))/(_)) ", bcolors.YELLOW)
		printColored("(_))  ((_) (_(_())(_))   ", bcolors.YELLOW)
		printColored("| |   | __||_   _|/ __|  ", bcolors.GREEN)
		printColored("| |__ | _|   | |  \\__ \\  ", bcolors.GREEN)
		printColored("|____||___|  |_|  |___/  \n", bcolors.GREEN)


	printColored("> Welcome to the Latest Essential Tatoe Server v{}".format(glob.VERSION), bcolors.GREEN)
	printColored("> Made by the ripple team", bcolors.GREEN)
	printColored("> {}https://github.com/osuripple/ripple".format(bcolors.UNDERLINE), bcolors.GREEN)
	printColored("> Press CTRL+C to exit\n",bcolors.GREEN)


def printNoNl(string):
	"""
	Print string without new line at the end

	string -- string to print
	"""

	print(string, end="")


def printColored(string, color):
	"""
	Print colored string

	string -- string to print
	color -- see bcolors.py
	"""

	print("{}{}{}".format(color, string, bcolors.ENDC))


def printError():
	"""Print error text FOR LOADING"""

	printColored("Error", bcolors.RED)


def printDone():
	"""Print error text FOR LOADING"""

	printColored("Done", bcolors.GREEN)


def printWarning():
	"""Print error text FOR LOADING"""

	printColored("Warning", bcolors.YELLOW)

def printGetScoresMessage(message):
	printColored("[get_scores] {}".format(message), bcolors.PINK)

def printSubmitModularMessage(message):
	printColored("[submit_modular] {}".format(message), bcolors.YELLOW)

def printBanchoConnectMessage(message):
	printColored("[bancho_connect] {}".format(message), bcolors.GREEN)

def printGetReplayMessage(message):
	printColored("[get_replay] {}".format(message), bcolors.GREEN)

def printRippMessage(message):
	printColored("[ripp] {}".format(message), bcolors.GREEN)
