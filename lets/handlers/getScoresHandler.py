import beatmap
import leaderboard
import tornado.web
from helpers import consoleHelper
from constants import bcolors

class handler(tornado.web.RequestHandler):
	"""
	Handler for /web/osu-osz2-getscores.php
	"""
	def get(self):
		# TODO: Debug stuff, remove
		'''print("GET ARGS::")
		for i in self.request.arguments:
			print ("{}={}".format(i, self.get_argument(i)))'''

		# TODO: Maintenance check

		# GET parameters
		md5 = self.get_argument("c")
		fileName = self.get_argument("f")
		beatmapSetID = self.get_argument("i")
		gameMode = self.get_argument("m")
		username = self.get_argument("us")
		consoleHelper.printColored("----", bcolors.PINK)
		fileNameShort = fileName[:32]+"..." if len(fileName) > 32 else fileName[:-4]
		consoleHelper.printGetScoresMessage("Requested beatmap {} ({})".format(fileNameShort, md5))

		# TODO: Login and arguments check

		# Create beatmap object and set its data
		bmap = beatmap.beatmap(md5, beatmapSetID)

		# Create leaderboard object, link it to bmap and get all scores
		lboard = leaderboard.leaderboard(username, gameMode, bmap)

		# Data to return
		data = ""
		data += bmap.getData()
		data += lboard.getScoresData()
		self.write(data)
