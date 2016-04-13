import tornado.web

class handler(tornado.web.RequestHandler):
	"""
	Handler for /web/bancho_connect.php
	"""
	def get(self):
		print("someone is connecting...")
		self.write("ysdii")
