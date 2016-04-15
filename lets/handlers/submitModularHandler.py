import tornado.web
from helpers import consoleHelper
from constants import bcolors
from helpers import aeshelper
from helpers import userHelper
import score
import os
import glob
from constants import gameModes
from constants import exceptions
from helpers import requestHelper

if os.path.isfile("ripp.py"):
	import ripp


MODULE_NAME = "submit_modular"
class handler(tornado.web.RequestHandler):
	"""
	Handler for /web/osu-submit-modular.php
	"""
	def post(self):
		try:
			# TODO: Debug stuff, remove
			'''print("POST ARGS::")
			for i in self.request.arguments:
				print ("{}={}".format(i, self.get_argument(i)))'''

			# Check arguments
			if requestHelper.checkArguments(self.request.arguments, ["score", "iv", "pass"]) == False:
				raise exceptions.invalidArgumentsException(MODULE_NAME)

			# TODO: Maintenance check

			# Get parameters
			scoreDataEnc = self.get_argument("score")
			iv = self.get_argument("iv")
			password = self.get_argument("pass")

			# Get right AES Key
			if "osuver" in self.request.arguments:
				aeskey = "osu!-scoreburgr---------{}".format(self.get_argument("osuver"))
			else:
				aeskey = "h89f2-890h2h89b34g-h80g134n90133"

			# Get score data
			consoleHelper.printColored("----", bcolors.YELLOW)
			consoleHelper.printSubmitModularMessage("Decrypting score data...")
			scoreData = aeshelper.decryptRinjdael(aeskey, iv, scoreDataEnc, True).split(":")
			username = scoreData[1].strip()

			# Login and ban check
			userID = userHelper.getUserID(username)
			if userID == 0:
				raise exceptions.loginFailedException(MODULE_NAME, userID)
			if userHelper.checkLogin(userID, password) == False:
				raise exceptions.loginFailedException(MODULE_NAME, username)
			if userHelper.getUserAllowed(userID) == 0:
				raise exceptions.userBannedException(MODULE_NAME, username)

			# Create score object and set its data
			consoleHelper.printSubmitModularMessage("Saving {}'s score on {}...".format(username, scoreData[0]))
			s = score.score()
			s.setDataFromScoreData(scoreData)

			# Calculate PP
			# NOTE: PP are std only
			if glob.pp == True and s.gameMode == gameModes.STD:
				s.calculatePP()

			# Save score in db
			s.saveScoreInDB()

			# Save replay
			if s.passed == True and s.completed == 3 and "score" in self.request.files:
				consoleHelper.printSubmitModularMessage("Saving replay ({})...".format(s.scoreID))
				replay = self.request.files["score"][0]["body"]
				with open(".data/replays/replay_{}.osr".format(s.scoreID), "wb") as f:
					f.write(replay)

			# Update users stats (total/ranked score, playcount, level and acc)
			consoleHelper.printSubmitModularMessage("Updating {}'s stats...".format(username))
			userHelper.updateStats(userID, s)

			# TODO: Set country flag

			# Update latest activity
			userHelper.updateLatestActivity(userID)

			# Done!
			consoleHelper.printSubmitModularMessage("Done!")
			self.write("ok")
		except exceptions.invalidArgumentsException:
			pass
		except exceptions.loginFailedException:
			self.write("error: pass")
		except exceptions.userBannedException:
			self.write("error: ban")
