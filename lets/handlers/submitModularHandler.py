import tornado.web

class handler(tornado.web.RequestHandler):
	"""
	Handler for /web/osu-submit-modular.php
	"""
	def get(self):
		self.write("submit modular here")
