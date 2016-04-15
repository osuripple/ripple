import tornado.web
from helpers import consoleHelper
from constants import bcolors
from helpers import aeshelper
from helpers import userHelper
import beatmap
import score
import os
import glob
from constants import gameModes

if os.path.isfile("ripp.py"):
	import ripp

class handler(tornado.web.RequestHandler):
	"""
	Handler for /web/osu-submit-modular.php
	"""
	def post(self):
		# TODO: Debug stuff, remove
		'''print("POST ARGS::")
		for i in self.request.arguments:
			print ("{}={}".format(i, self.get_argument(i)))'''

		consoleHelper.printColored("----", bcolors.YELLOW)

		# TODO: Maintenance check
		# TODO: Login check
		# TODO: Ban check

		# Get parameters
		# TODO: Argument check
		scoreDataEnc = self.get_argument("score")
		iv = self.get_argument("iv")
		password = self.get_argument("pass")

		# Get right AES Key
		if "osuver" in self.request.arguments:
			aeskey = "osu!-scoreburgr---------{}".format(self.get_argument("osuver"))
		else:
			aeskey = "h89f2-890h2h89b34g-h80g134n90133"

		# Get score data
		consoleHelper.printSubmitModularMessage("Decrypting score data...")
		scoreData = aeshelper.decryptRinjdael(aeskey, iv, scoreDataEnc, True).split(":")
		username = scoreData[1].strip()
		userID = userHelper.getUserID(username)
		if userID == 0:
			consoleHelper.printColored("[!] User {} doesn't exist. Score not saved.".format(username), bcolors.RED)
			return

		# Create score object, set its data and add it to db
		consoleHelper.printSubmitModularMessage("Saving {}'s score on {}...".format(username, scoreData[0]))
		s = score.score()
		s.setDataFromScoreData(scoreData)
		s.saveScoreInDB()

		# Save replay
		if s.passed == True and s.completed == 3 and "score" in self.request.files:
			consoleHelper.printSubmitModularMessage("Saving replay ({})...".format(s.scoreID))
			replay = self.request.files["score"][0]["body"]
			with open(".data/replays/replay_{}.osr".format(s.scoreID), "wb") as f:
				f.write(replay)

		# Calculate PP
		pp = 0
		if glob.pp == True and s.gameMode == gameModes.STD:
			consoleHelper.printRippMessage("Calculating PP. w00t p00t...")
			# Create beatmap object
			b = beatmap.beatmap(s.fileMd5, 0)

			# Create an instance of the magic pp calculator and calculate pp
			fo = ripp.algo(b, s)
			pp = fo.getPP()
			consoleHelper.printRippMessage("Aim PP: {}".format(fo.aimValue))
			consoleHelper.printRippMessage("Speed PP: {}".format(fo.speedValue))
			consoleHelper.printRippMessage("Acc PP: {}".format(fo.accValue))
			consoleHelper.printRippMessage("Total PP: {}".format(pp))

		# Update users stats (total/ranked score, playcount, level and acc)
		consoleHelper.printSubmitModularMessage("Updating {}'s stats...".format(username))
		userHelper.updateStats(userID, s, pp)


		# TODO: Update country flag
		# TODO: Update latest activity
		# TODO: Output errors

		consoleHelper.printSubmitModularMessage("Done!")
		self.write("ok")
