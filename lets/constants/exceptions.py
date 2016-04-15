from helpers import consoleHelper
from constants import bcolors

class invalidArgumentsException(Exception):
	def __init__(self, handler):
		consoleHelper.printColored("[{}] Invalid arguments".format(handler), bcolors.RED)

class loginFailedException(Exception):
	def __init__(self, handler, who):
		consoleHelper.printColored("[{}] {}'s Login failed".format(handler, who), bcolors.RED)

class userBannedException(Exception):
	def __init__(self, handler, who):
		consoleHelper.printColored("[{}] {} is banned".format(handler, who), bcolors.RED)
