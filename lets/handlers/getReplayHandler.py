import os
import tornado.web
from helpers import consoleHelper

class handler(tornado.web.RequestHandler):
	"""
	Handler for osu-getreplay.php
	"""
	def get(self):
		replayID = self.get_argument("c")
		consoleHelper.printGetReplayMessage("Serving replay_{}.osr".format(replayID))

		# TODO: Login check
		fileName = ".data/replays/replay_{}.osr".format(replayID)
		if os.path.isfile(fileName):
			with open(fileName, "rb") as f:
				fileContent = f.read()
			self.write(fileContent)
		else:
			self.write("")
