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
		if (os.path.isfile(self.fileName)):
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

			self.config.get("server","port")

			self.config.get("osuapi","apiurl")
			self.config.get("osuapi","apikey")

			self.config.get("discord","enable")
			self.config.get("discord","boturl")
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
		self.config.set("server", "port", "5002")

		self.config.add_section("osuapi")
		self.config.set("osuapi", "apiurl", "https://osu.ppy.sh/api")
		self.config.set("osuapi", "apikey", "YOUR_OSU_API_KEY_HERE")

		self.config.add_section("discord")
		self.config.set("discord", "enable", "False")
		self.config.set("discord", "boturl", "")

		# Write ini to file and close
		self.config.write(f)
		f.close()
