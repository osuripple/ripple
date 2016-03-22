import match
import glob
import serverPackets

class matchList:
	matches = []
	usersInLobby = []
	lastID = 1

	def __init__(self):
		"""Initialize a matchList object"""
		self.matches = []	# position 0 is bugged in client
		self.usersInLobby = []
		self.lastID = 1

	def newMatch(self, __matchName, __matchPassword, __beatmapID, __beatmapName, __beatmapMD5, __gameMode, __seed):
		"""
		Add a new match to matches list

		__matchName -- match name, string
		__matchPassword -- match md5 password. Leave empty for no password
		__beatmapID -- beatmap ID
		__beatmapName -- beatmap name, string
		__beatmapMD5 -- beatmap md5 hash, string
		__gameMode -- game mode ID. See gameModes.py
		__seed -- idk, int

		__return -- match ID
		"""
		# Add a new match to matches list
		matchID = self.lastID
		self.lastID+=1
		self.matches.append(match.match(matchID, __matchName, __matchPassword, __beatmapID, __beatmapName, __beatmapMD5, __gameMode, __seed))
		return self.matches[len(self.matches)-1]


	def getMatchFromMatchID(self, __matchID):
		"""
		Get match object from its matchID

		__matchID -- matchID int
		return -- match object if found, None if not found
		"""

		# Loop though all matches
		for i in self.matches:
			if (i.matchID == __matchID):
				return i

		# No match found, return None
		return None


	def lobbyUserJoin(self, __userID):
		"""
		Add userID to users in lobby

		__userID -- user who joined mp lobby
		"""

		# Make sure the user is not already in mp lobby
		if (__userID not in self.usersInLobby):
			self.usersInLobby.append(__userID)


	def lobbyUserPart(self, __userID):
		"""
		Remove userID from users in lobby

		__userID -- user who left mp lobby
		"""

		# Make sure the user is in mp lobby
		if (__userID in self.usersInLobby):
			self.usersInLobby.remove(__userID)


	def disposeMatch(self, __matchID):
		"""
		Destroy match object with id = __matchID

		__matchID -- ID of match to dispose
		return -- True if success, False if error
		"""

		# Remove match object
		for i in self.matches:
			if (i.matchID == __matchID):
				self.matches.remove(i)

		# Send match dispose packet to everyone in lobby
		for i in self.usersInLobby:
			token = glob.tokens.getTokenFromUserID(i)
			if (token != None):
				token.enqueue(serverPackets.matchDispose(__matchID))

		return False
