import bcolors

def printServerStartHeader(__asciiArt):
	if (__asciiArt == True):
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

	print(bcolors.GREEN+"> Welcome to pep.py osu! Server v0.5\n> Made by the ripple team\n> Press CTRL+C to exit"+bcolors.ENDC+"\n")

def printNoNl(__string):
	print(__string, end="")

def printColored(__string, __color):
	print(__color+__string+bcolors.ENDC)

def printError():
	printColored("Error", bcolors.REd)

def printDone():
	printColored("Done", bcolors.GREEN)

def printWarning():
	printColored("Warning", bcolors.YELLOW)
