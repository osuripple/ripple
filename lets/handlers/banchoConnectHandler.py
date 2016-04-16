import tornado.web
from helpers import discordBotHelper
from helpers import userHelper
from helpers import consoleHelper
import glob
from constants import exceptions
from helpers import requestHelper

MODULE_NAME = "bancho_connect"
class handler(tornado.web.RequestHandler):
	"""
	Handler for /web/bancho_connect.php
	"""
	def get(self):
		try:
			# Argument check
			if requestHelper.checkArguments(self.request.arguments, ["u", "h"]) == False:
				raise exceptions.invalidArgumentsException(MODULE_NAME)

			# Get user ID
			username = self.get_argument("u")
			userID = userHelper.getID(username)
			if userID == None:
				raise exceptions.loginFailedException(MODULE_NAME, username)

			# Check login
			consoleHelper.printBanchoConnectMessage("{} ({}) wants to connect".format(username, userID))
			if userHelper.checkLogin(userID, self.get_argument("h")) == False:
				raise exceptions.loginFailedException(MODULE_NAME, username)

			# Ban check
			if userHelper.getAllowed(userID) == 0:
				raise exceptions.userBannedException(MODULE_NAME, username)

			# Update latest activity
			userHelper.updateLatestActivity(userID)

			# Get country and output it
			country = glob.db.fetch("SELECT country FROM users_stats WHERE id = ?", [userID])["country"]
			self.write(country)
		except exceptions.invalidArgumentsException:
			pass
		except exceptions.loginFailedException:
			self.write("error: pass")
		except exceptions.userBannedException:
			pass
