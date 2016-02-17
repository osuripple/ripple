import os
import configparser

class config:
	config = configparser.ConfigParser()
	default = True      # if true, we have generated a default config.ini

	# Check if config.ini exists and load/generate it
	def __init__(self):
		if (os.path.isfile("config.ini")):
			# config.ini found, load it
			self.config.read("config.ini")
			self.default = False
		else:
			# config.ini not found, generate a default one
			self.generateDefaultConfig()
			self.default = True

	# Check if config.ini has all needed the keys
	def checkConfig(self):
		try:
			# Try to get all the required keys
			self.config.get("db","host")
			self.config.get("db","username")
			self.config.get("db","password")
			self.config.get("db","database")
			self.config.get("server","host")
			self.config.get("server","port")
			self.config.get("server","threaded")
			self.config.get("server","debug")
			return True
		except:
			return False

	# Generate a default config.ini
	def generateDefaultConfig(self):
		# Open config.ini in write mode
		f = open("config.ini", "w")

		# Set keys to config object
		self.config.add_section("db")
		self.config.set("db", "host", "localhost")
		self.config.set("db", "username", "root")
		self.config.set("db", "password", "")
		self.config.set("db", "database", "ripple")

		self.config.add_section("server")
		self.config.set("server", "host", "0.0.0.0")
		self.config.set("server", "port", "80")
		self.config.set("server", "threaded", "True")
		self.config.set("server", "debug", "False")

		# Write ini to file and close
		self.config.write(f)
		f.close()
