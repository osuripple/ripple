import match
import glob
import serverPackets

class matchList:
	matches = {}
	usersInLobby = []
	lastID = 1

	def __init__(self):
		"""Initialize a matchList object"""
		self.matches = {}
		self.usersInLobby = []
		self.lastID = 1

	def createMatch(self, __matchName, __matchPassword, __beatmapID, __beatmapName, __beatmapMD5, __gameMode, __hostUserID):
		"""
		Add a new match to matches list

		__matchName -- match name, string
		__matchPassword -- match md5 password. Leave empty for no password
		__beatmapID -- beatmap ID
		__beatmapName -- beatmap name, string
		__beatmapMD5 -- beatmap md5 hash, string
		__gameMode -- game mode ID. See gameModes.py
		__hostUserID -- user id of who created the match
		return -- match ID
		"""
		# Add a new match to matches list
		matchID = self.lastID
		self.lastID+=1
		self.matches[matchID] = match.match(matchID, __matchName, __matchPassword, __beatmapID, __beatmapName, __beatmapMD5, __gameMode, __hostUserID)
		return matchID


	def lobbyUserJoin(self, __userID):
		"""
		Add userID to users in lobby

		__userID -- user who joined mp lobby
		"""

		# Make sure the user is not already in mp lobby
		if __userID not in self.usersInLobby:
			# We don't need to join #lobby, client will automatically send a packet for it
			self.usersInLobby.append(__userID)


	def lobbyUserPart(self, __userID):
		"""
		Remove userID from users in lobby

		__userID -- user who left mp lobby
		"""

		# Make sure the user is in mp lobby
		if __userID in self.usersInLobby:
			# Part lobby and #lobby channel
			self.usersInLobby.remove(__userID)


	def disposeMatch(self, __matchID):
		"""
		Destroy match object with id = __matchID

		__matchID -- ID of match to dispose
		"""

		# Make sure the match exists
		if __matchID not in self.matches:
			return

		# Remove match object
		self.matches.pop(__matchID)

		# Send match dispose packet to everyone in lobby
		for i in self.usersInLobby:
			token = glob.tokens.getTokenFromUserID(i)
			if token != None:
				token.enqueue(serverPackets.disposeMatch(__matchID))
