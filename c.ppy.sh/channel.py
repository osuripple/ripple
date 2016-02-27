class channel:
	name = ""
	description = ""
	connectedUsers = []

	publicRead = False
	publicWrite = False
	moderated = False

	def __init__(self, __name, __description, __publicRead, __publicWrite):
		self.name = __name
		self.description = __description
		self.publicRead = __publicRead
		self.publicWrite = __publicWrite


	def userJoin(self, __userID):
		connectedUsers = self.connectedUsers
		if (__userID not in connectedUsers):
			connectedUsers.append(__userID)


	def userPart(self, __userID):
		connectedUsers = self.connectedUsers
		if (__userID in connectedUsers):
			connectedUsers.remove(__userID)


	def getConnectedUsers(self):
		return self.connectedUsers


	def getConnectedUsersCount(self):
		return len(self.connectedUsers)
