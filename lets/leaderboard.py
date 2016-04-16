import score
import glob

class leaderboard:
	def __init__(self, username, gameMode, beatmap, setScores = True):
		"""
		Initialize a leaderboard object

		username -- username of who's requesting the leaderboard. None if not known
		gameMode -- requested gameMode
		beatmap -- beatmap objecy relative to this leaderboard
		setScores -- if True, will get personal/top 50 scores automatically. Optional. Default: True
		"""
		self.scores = []				# list containing all score objects. First object is personal best
		self.personalBestRank = -1		# our personal best rank, -1 if not found yet
		self.username = username		# username of who's requesting the leaderboard. None if not known
		self.gameMode = gameMode		# requested gameMode
		self.beatmap = beatmap			# beatmap objecy relative to this leaderboard
		if setScores == True:
			self.setScores()


	def setScores(self):
		"""
		Set scores list
		"""
		# Reset score list
		self.scores = []

		# Find personal best score
		if self.username != None:
			personalBestScore = glob.db.fetch("SELECT id FROM scores WHERE username = ? AND beatmap_md5 = ? AND play_mode = ? AND completed = 3", [self.username, self.beatmap.fileMD5, self.gameMode])
		else:
			personalBestScore = None

		# Output our personal best if found
		if personalBestScore != None:
			s = score.score(personalBestScore["id"])
			self.scores.append(s)
		else:
			# No personal best
			self.scores.append(-1)

		# Top 50 scores
		topScores = glob.db.fetchAll("SELECT id FROM scores WHERE beatmap_md5 = ? AND play_mode = ? AND completed = 3 ORDER BY score DESC  LIMIT 50", [self.beatmap.fileMD5, self.gameMode])
		c = 1
		if topScores != None:
			for i in topScores:
				# Create score object and set its data
				s = score.score(i["id"], c)

				# Check if this top 50 score is our personal best
				if s.playerName == self.username:
					self.personalBestRank = c

				# Add this score to scores list and increment rank
				self.scores.append(s)
				c+=1


	def getScoresData(self):
		"""
		Return scores data for getscores

		return -- score data in getscores format
		"""
		data = ""

		# Output personal best
		if self.scores[0] == -1:
			# We don't have a personal best score
			data += "\n"
		else:
			# We have a personal best score
			if self.personalBestRank == -1:
				# ...but we don't know our rank in leaderboard. Get it.
				c=1
				scores = glob.db.fetchAll("SELECT DISTINCT username FROM scores WHERE beatmap_md5 = ? AND play_mode = ? AND completed = 3 ORDER BY score DESC", [self.beatmap.fileMD5, self.gameMode])
				if scores != None:
					for i in scores:
						if scores["username"] == self.username:
							self.personalBestRank = c
						c+=1

			# Set personal best score rank
			self.scores[0].setRank(self.personalBestRank)
			data += self.scores[0].getData()

		# Output top 50 scores
		for i in self.scores[1:]:
			data += i.getData()

		return data
