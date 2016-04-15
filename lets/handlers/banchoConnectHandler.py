import tornado.web
from helpers import discordBotHelper

class handler(tornado.web.RequestHandler):
	"""
	Handler for /web/bancho_connect.php
	"""
	def get(self):
		self.write("it")
