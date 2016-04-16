import os
import configparser

class config:
	"""
	config.ini object

	config -- list with ini data
	default -- if true, we have generated a default config.ini
	"""

	config = configparser.ConfigParser()
	fileName = ""		# config filename
	default = True

	# Check if config.ini exists and load/generate it
	def __init__(self, __file):
		"""
		Initialize a config object

		__file -- filename
		"""

		self.fileName = __file
		if os.path.isfile(self.fileName):
			# config.ini found, load it
			self.config.read(self.fileName)
			self.default = False
		else:
			# config.ini not found, generate a default one
			self.generateDefaultConfig()
			self.default = True


	# Check if config.ini has all needed the keys
	def checkConfig(self):
		"""
		Check if this config has the required keys

		return -- True if valid, False if not
		"""

		try:
			# Try to get all the required keys
			self.config.get("db","host")
			self.config.get("db","username")
			self.config.get("db","password")
			self.config.get("db","database")
			self.config.get("db","pingtime")

			self.config.get("server","server")
			self.config.get("server","host")
			self.config.get("server","port")
			self.config.get("server","localizeusers")
			self.config.get("server","outputpackets")
			self.config.get("server","outputrequesttime")
			self.config.get("server","timeouttime")
			self.config.get("server","timeoutlooptime")

			if self.config["server"]["server"] == "flask":
				# Flask only config
				self.config.get("flask","threaded")
				self.config.get("flask","debug")
				self.config.get("flask","logger")

			self.config.get("ci","key")
			return True
		except:
			return False


	# Generate a default config.ini
	def generateDefaultConfig(self):
		"""Open and set default keys for that config file"""

		# Open config.ini in write mode
		f = open(self.fileName, "w")

		# Set keys to config object
		self.config.add_section("db")
		self.config.set("db", "host", "localhost")
		self.config.set("db", "username", "root")
		self.config.set("db", "password", "")
		self.config.set("db", "database", "ripple")
		self.config.set("db", "pingtime", "600")

		self.config.add_section("server")
		self.config.set("server", "server", "tornado")
		self.config.set("server", "host", "0.0.0.0")
		self.config.set("server", "port", "5001")
		self.config.set("server", "localizeusers", "1")
		self.config.set("server", "outputpackets", "0")
		self.config.set("server", "outputrequesttime", "0")
		self.config.set("server", "timeoutlooptime", "100")
		self.config.set("server", "timeouttime", "100")

		self.config.add_section("flask")
		self.config.set("flask", "threaded", "1")
		self.config.set("flask", "debug", "0")
		self.config.set("flask", "logger", "0")

		self.config.add_section("ci")
		self.config.set("ci", "key", "changeme")

		# Write ini to file and close
		self.config.write(f)
		f.close()
