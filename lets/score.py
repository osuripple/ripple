from lets import glob
from helpers import userHelper
from helpers import scoreHelper

class score:
	def __init__(self, scoreID = None, rank = None):
		"""
		Initialize a (empty) score object.

		scoreID -- score ID, used to get score data from db. Optional.
		rank -- score rank. Optional
		"""
		self.scoreID = 0
		self.playerName = "nospe"
		self.score = 0
		self.maxCombo = 0
		self.c50 = 0
		self.c100 = 0
		self.c300 = 0
		self.cMiss = 0
		self.cKatu = 0
		self.cGeki = 0
		self.fullCombo = False
		self.mods = 0
		self.playerUserID = 0
		self.rank = 1	# can be empty string too
		self.date = 0
		self.hasReplay = 0

		self.fileMd5 = None
		self.passed = False
		self.playDateTime = 0
		self.gameMode = 0
		self.completed = 0

		self.accuracy = 0.00

		self.rankedScoreIncrease = 0

		if scoreID != None:
			self.setDataFromDB(scoreID, rank)

	def calculateAccuracy(self):
		"""
		Calculate and set accuracy for that score
		"""
		if (self.gameMode == 0):
			# std
			totalPoints = self.c50*50+self.c100*100+self.c300*300
			totalHits = self.c300+self.c100+self.c50+self.cMiss
			self.accuracy = totalPoints/(totalHits*300)
		elif (self.gameMode == 1):
			# taiko
			totalPoints = (self.c100*50)+(self.c300*100)
			totalHits = self.cMiss+self.c100+self.c300
			self.accuracy = totalPoints/(totalHits*100)
		elif (self.gameMode == 2):
			# ctb
			fruits = self.c300+self.c100+self.c50
			totalFruits = fruits+self.cMiss+self.cKatu
			self.accuracy = fruits/totalFruits
		elif (self.gameMode == 3):
			# mania
			totalPoints = self.c50*50+self.c100*100+self.cKatu*200+self.c300*300+self.cGeki*300
			totalHits = self.cMiss+self.c50+self.c100+self.c300+self.cGeki+self.cKatu
			self.accuracy = totalPoints / (totalHits * 300)
		else:
			# unknown gamemode
			self.accuracy = 0

	def setRank(self, rank):
		"""
		Force a score rank

		rank -- new score rank
		"""
		self.rank = rank

	def setDataFromDB(self, scoreID, rank = None):
		"""
		Set this object's score data from db

		scoreID -- score ID
		rank -- rank in leaderboard. Optional.
		"""
		data = glob.db.fetch("SELECT * FROM scores WHERE id = ?", [scoreID])
		if (data != None):
			self.scoreID = scoreID
			self.playerName = data["username"]
			self.score = data["score"]
			self.maxCombo = data["max_combo"]
			self.c50 = data["50_count"]
			self.c100 = data["100_count"]
			self.c300 = data["300_count"]
			self.cMiss = data["misses_count"]
			self.cKatu = data["katus_count"]
			self.cGeki = data["gekis_count"]
			self.fullCombo = True if data["full_combo"] == 1 else False
			self.mods = data["mods"]
			self.playerUserID = userHelper.getUserID(self.playerName)
			self.rank = rank if rank != None else ""
			self.date = data["time"]
			self.calculateAccuracy()

	def setDataFromScoreData(self, scoreData):
		"""
		Set this object's score data from scoreData list (submit modular)

		scoreData -- scoreData list
		"""
		if len(scoreData) >= 16:
			self.fileMd5 = scoreData[0]
			self.playerName = scoreData[1].strip()
			# ??? = scoreData[2]
			self.c300 = int(scoreData[3])
			self.c100 = int(scoreData[4])
			self.c50 = int(scoreData[5])
			self.cGeki = int(scoreData[6])
			self.cKatu = int(scoreData[7])
			self.cMiss = int(scoreData[8])
			self.score = int(scoreData[9])
			self.maxCombo = int(scoreData[10])
			self.fullCombo = True if scoreData[11] == 'True' else False
			#self.rank = scoreData[12]
			self.mods = int(scoreData[13])
			self.passed = True if scoreData[14] == 'True' else False
			self.gameMode = int(scoreData[15])
			self.playDateTime = int(scoreData[16])
			self.calculateAccuracy()
			#osuVersion = scoreData[17]


	def getData(self):
		"""Return score row relative to this score for getscores"""
		return "{}|{}|{}|{}|{}|{}|{}|{}|{}|{}|{}|{}|{}|{}|{}|1\n".format(
			self.scoreID,
			self.playerName,
			self.score,
			self.maxCombo,
			self.c50,
			self.c100,
			self.c300,
			self.cMiss,
			self.cKatu,
			self.cGeki,
			self.fullCombo,
			self.mods,
			self.playerUserID,
			self.rank,
			self.date)

	def saveScoreInDB(self):
		"""
		Save this score in DB (if passed and mods are valid)
		"""
		if self.passed == True and scoreHelper.isRankable(self.mods):
			# Get right "completed" value
			personalBest = glob.db.fetch("SELECT score FROM scores WHERE username = ? AND beatmap_md5 = ? AND play_mode = ? AND completed = 3", [self.playerName, self.fileMd5, self.gameMode])
			if personalBest == None:
				# This is our first score on this map, so it's our best score
				self.completed = 3
				self.rankedScoreIncrease = self.score
			else:
				# Compare personal best's score with current score
				if self.score > personalBest["score"]:
					self.completed = 3
					self.rankedScoreIncrease = self.score-personalBest["score"]
				else:
					self.completed = 2
					self.rankedScoreIncrease = 0

			# Add this score
			query = "INSERT INTO scores (id, beatmap_md5, username, score, max_combo, full_combo, mods, 300_count, 100_count, 50_count, katus_count, gekis_count, misses_count, time, play_mode, completed, accuracy) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);"
			glob.db.execute(query, [self.fileMd5, self.playerName, self.score, self.maxCombo, 1 if self.fullCombo == True else 0, self.mods, self.c300, self.c100, self.c50, self.cKatu, self.cGeki, self.cMiss, self.playDateTime, self.gameMode, self.completed, self.accuracy*100])

			# Get score id
			self.scoreID = glob.db.connection.insert_id()
