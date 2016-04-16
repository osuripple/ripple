class channel:
	"""
	A chat channel

	name -- channel name
	description -- channel description
	connectedUsers -- connected users IDs list
	publicRead -- bool
	publicWrite -- bool
	moderated -- bool
	"""

	name = ""
	description = ""
	connectedUsers = []

	publicRead = False
	publicWrite = False
	moderated = False

	def __init__(self, __name, __description, __publicRead, __publicWrite):
		"""
		Create a new chat channel object

		__name -- channel name
		__description -- channel description
		__publicRead -- bool, if true channel can be read by everyone, if false it can be read only by mods/admins
		__publicWrite -- bool, same as public read but relative to write permissions
		"""

		self.name = __name
		self.description = __description
		self.publicRead = __publicRead
		self.publicWrite = __publicWrite
		self.connectedUsers = []


	def userJoin(self, __userID):
		"""
		Add a user to connected users

		__userID -- user ID that joined the channel
		"""

		if __userID not in self.connectedUsers:
			self.connectedUsers.append(__userID)


	def userPart(self, __userID):
		"""
		Remove a user from connected users

		__userID -- user ID that left the channel
		"""

		connectedUsers = self.connectedUsers
		if __userID in connectedUsers:
			connectedUsers.remove(__userID)


	def getConnectedUsers(self):
		"""
		Get connected user IDs list

		return -- connectedUsers list
		"""

		return self.connectedUsers


	def getConnectedUsersCount(self):
		"""
		Count connected users

		return -- connected users number
		"""

		return len(self.connectedUsers)
