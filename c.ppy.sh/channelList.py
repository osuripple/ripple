import glob
import channel

class channelList:
	"""
	Channel list

	channels -- dictionary. key: channel name, value: channel object
	"""

	channels = {}


	def loadChannels(self):
		"""
		Load chat channels from db and add them to channels dictionary
		"""

		# Get channels from DB
		channels = glob.db.fetchAll("SELECT * FROM bancho_channels")

		# Add each channel if needed
		for i in channels:
			if i["name"] not in self.channels:
				publicRead = True if i["public_read"] == 1 else False
				publicWrite = True if i["public_write"] == 1 else False
				self.addChannel(i["name"], i["description"], publicRead, publicWrite)


	def addChannel(self, __name, __description, __publicRead, __publicWrite):
		"""
		Add a channel object to channels dictionary

		__name -- channel name
		__description -- channel description
		__publicRead -- bool, if true channel can be read by everyone, if false it can be read only by mods/admins
		__publicWrite -- bool, same as public read but relative to write permissions
		"""

		self.channels[__name] = channel.channel(__name, __description, __publicRead, __publicWrite)
