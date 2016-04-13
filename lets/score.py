from lets import glob
from helpers import userHelper

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

		if scoreID != None:
			self.setData(scoreID, rank)

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

	def setData(self, scoreID, rank = None):
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

	def setRank(self, rank):
		"""
		Force a score rank

		rank -- new score rank
		"""
		self.rank = rank
