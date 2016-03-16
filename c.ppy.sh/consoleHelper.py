"""Some console related functions"""

import bcolors

def printServerStartHeader(asciiArt):
	"""Print server start header with optional ascii art

	asciiArt -- if True, will print ascii art too"""

	if (asciiArt == True):
		print(bcolors.GREEN)
		print("           _                 __")
		print("          (_)              /  /")
		print("   ______ __ ____   ____  /  /____")
		print("  /  ___/  /  _  \\/  _  \\/  /  _  \\")
		print(" /  /  /  /  /_) /  /_) /  /  ____/")
		print("/__/  /__/  .___/  .___/__/ \\_____/")
		print("        /  /   /  /")
		print("       /__/   /__/\r\n")
		print("                          .. o  .")
		print("                         o.o o . o")
		print("                        oo...")
		print("                    __[]__")
		print("    phwr-->  _\\:D/_/o_o_o_|__     u wot m8")
		print("             \\\"\"\"\"\"\"\"\"\"\"\"\"\"\"/")
		print("              \\ . ..  .. . /")
		print("^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^")
		print(bcolors.ENDC)

	print("{}{}\n{}\n{}{}\n".format(bcolors.GREEN, "> Welcome to pep.py osu! Server v0.5", "> Made by the ripple team", "> Press CTRL+C to exit", bcolors.ENDC))


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
