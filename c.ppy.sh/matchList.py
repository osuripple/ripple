import match

class matchList:
	matches = []

	def __init__(self):
		"""Initialize a matchList object"""
		self.matches = []

	def newMatch(self, __matchName, __matchPassword, __beatmapID, __beatmapName, __beatmapMD5, __hostUserID, __seed):
		"""
		Add a new match to matches list

		__matchName -- match name, string
		__matchPassword -- match md5 password. Leave empty for no password
		__beatmapID -- beatmap ID
		__beatmapName -- beatmap name, string
		__beatmapMD5 -- beatmap md5 hash, string
		__hostUserID -- user ID who has the host
		__seed -- idk, int
		"""
		# Add a new match to matches list
		matchID = len(self.matches)
		self.matches.append(match.match(matchID, __matchName, __matchPassword, __beatmapID, __beatmapName, __beatmapMD5, __hostUserID, __seed))
