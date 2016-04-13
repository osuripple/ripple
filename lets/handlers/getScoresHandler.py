import beatmap
import leaderboard
import tornado.web

class handler(tornado.web.RequestHandler):
	"""
	Handler for /web/osu-osz2-getscores.php
	"""
	def get(self):
		# TODO: Debug stuff, remove
		print("GET ARGS::")
		for i in self.request.arguments:
			print ("{}={}".format(i, self.get_argument(i)))


		# GET parameters
		md5 = self.get_argument("c")
		beatmapSetID = self.get_argument("i")
		gameMode = self.get_argument("m")
		username = self.get_argument("us")

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
