import glob
import channel

class channelList:
	# Channels list
	# Contains channel objects
	channels = {}

	def loadChannels(self):
		"""Load chat channels from db"""

		channels = glob.db.fetchAll("SELECT * FROM bancho_channels")
		for i in channels:
			publicRead = True if i["public_read"] == 1 else False
			publicWrite = True if i["public_write"] == 1 else False
			self.channels[i["name"]] = channel.channel(i["name"], i["description"], publicRead, publicWrite)
