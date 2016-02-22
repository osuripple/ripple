import glob

class channelList:
	# Channels list
	# Index: Channel name
	# Value: Array [description, [connectedUsers]]
	channels = {}

	# TODO: Init function that sets up channels

	def addChannel(self, __channel, __description):
		"""Add a channel to channel list

		__channel -- Channel name
		__description -- Channel description"""

		self.channels[__channel] = [__description, []]

	def loadChannels(self):
		"""Load chat channels from db"""
		
		channels = glob.db.fetchAll("SELECT * FROM bancho_channels")
		for i in channels:
			self.addChannel(i["name"], i["description"])


	def getConnectedUsers(self, __channel):
		"""Count __channel connected users

		__channel -- Channel name

		return -- connected users count"""

		return len(self.channels[__channel][1])


	def joinChannel(self, __channel, __userID):
		"""Add __userID to __channel's connected users

		__channel -- Channel name
		__userID -- User ID"""

		connectedUsers = self.channels[__channel][1]
		if (__userID not in connectedUsers):
			connectedUsers.append(__userID)


	def partChannel(self, __channel, __userID):
		"""Remove __userID to __channel's connected users

		__channel -- Channel name
		__userID -- User ID"""

		connectedUsers = self.channels[__channel][1]
		if (__userID in connectedUsers):
			connectedUsers.pop(__userID)
