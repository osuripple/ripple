from constants import rankedStatuses
import score
from helpers import osuapiHelper
import glob
import time
from helpers import consoleHelper
from constants import bcolors

class beatmap:
	def __init__(self, md5 = None, beatmapSetID = None):
		"""
		Initialize a beatmap object.

		md5 -- beatmap md5. Optional.
		beatmapSetID -- beatmapSetID. Optional.
		"""
		self.songName = ""
		self.fileMD5 = ""
		self.rankedStatus = rankedStatuses.NOT_SUBMITTED
		self.beatmapID = 0
		self.beatmapSetID = 0
		self.offset = 0		# Won't implement
		self.rating = 10.0 	# Won't implement
		if md5 != None and beatmapSetID != None:
			self.setData(md5, beatmapSetID)

	def addBeatmapToDB(self):
		"""
		Add current beatmap data in db if not in yet
		"""
		# Make sure the beatmap is not already in db
		consoleHelper.printColored("Saving beatmap data in db...", bcolors.PINK)
		bid = glob.db.fetch("SELECT id FROM beatmaps WHERE beatmap_md5 = ?", [self.fileMD5])
		if (bid != None):
			# This beatmap is already in db, remove old record
			consoleHelper.printColored("Deleting old beatmap data", bcolors.PINK)
			glob.db.execute("DELETE FROM beatmaps WHERE id = ?", [bid["id"]])

		# Add new beatmap data
		consoleHelper.printColored("Saving new data...", bcolors.PINK)
		glob.db.execute("INSERT INTO `beatmaps` (`id`, `beatmap_id`, `beatmapset_id`, `beatmap_md5`, `song_name`, `ranked`, `latest_update`) VALUES (NULL, ?, ?, ?, ?, ?, ?);", [
			self.beatmapID,
			self.beatmapSetID,
			self.fileMD5,
			self.songName,
			self.rankedStatus,
			int(time.time())
		])

	def setDataFromDB(self, md5):
		"""
		Set this object's beatmap data from db.

		md5 -- beatmap md5
		return -- True if set, False if not set
		"""
		# Get data from DB
		consoleHelper.printColored("Trying to get beatmap data from db", bcolors.PINK)
		data = glob.db.fetch("SELECT * FROM beatmaps WHERE beatmap_md5 = ?", [md5])

		# Make sure the query returned something
		if (data == None):
			consoleHelper.printColored("No beatmap data in db", bcolors.PINK)
			return False

		# Make sure the beatmap data in db is not too old
		if (time.time() > data["latest_update"]+86400):
			consoleHelper.printColored("Beatmap db data is too old", bcolors.PINK)
			return False

		# Data in DB, set beatmap data
		consoleHelper.printColored("Beatmap data is in db", bcolors.PINK)
		self.songName = data["song_name"]
		self.fileMD5 = md5
		self.rankedStatus = data["ranked"]
		self.beatmapID = data["beatmap_id"]
		self.beatmapSetID = data["beatmapset_id"]
		return True

	def setDataFromOsuApi(self, md5, beatmapSetID):
		"""
		Set this object's beatmap data from osu!api.

		md5 -- beatmap md5
		beatmapSetID -- beatmap set ID, used to check if a map is outdated
		return -- True if set, False if not set
		"""
		consoleHelper.printColored("Trying to get beatmap data from osu!api", bcolors.PINK)
		data = osuapiHelper.osuApiRequest("get_beatmaps", "h={}".format(md5))
		if (data == None):
			# Error while retreiving data from MD5, check with beatmap set ID
			data = osuapiHelper.osuApiRequest("get_beatmaps", "s={}".format(beatmapSetID))
			if (data == None):
				# Still no data, beatmap is not submitted
				return False
			else:
				# We have some data, but md5 doesn't match. Beatmap is outdated
				self.rankedStatus = rankedStatuses.NEED_UPDATE
				return True

		# We have data from osu!api, set beatmap data
		consoleHelper.printColored("Got beatmap data from osu!api", bcolors.PINK)
		self.songName = "{} - {}".format(data["artist"], data["title"])
		self.fileMD5 = md5
		self.rankedStatus = convertRankedStatus(data["approved"])
		self.beatmapID = data["beatmap_id"]
		self.beatmapSetID = data["beatmapset_id"]
		return True

	def setData(self, md5, beatmapSetID):
		"""
		Set this object's beatmap data from highest level possible.

		md5 -- beatmap MD5
		beatmapSetID -- beatmap set ID
		"""
		# Get beatmap from db
		dbResult = self.setDataFromDB(md5)

		if (dbResult == False):
			# If this beatmap is not in db, get it from osu!api
			apiResult = self.setDataFromOsuApi(md5, beatmapSetID)
			if (apiResult == False):
				# If it's not even in osu!api, this beatmap is not submitted
				self.rankedStatus = rankedStatuses.NOT_SUBMITTED
			elif (self.rankedStatus != rankedStatuses.NOT_SUBMITTED and self.rankedStatus != rankedStatuses.NEED_UPDATE):
				# We get beatmap data from osu!api, save it in db
				self.addBeatmapToDB()

	def getData(self):
		"""
		Return this beatmap's data (header) for getscores

		return -- beatmap header for getscores
		"""
		totalScores = 0
		data = "{}|false".format(self.rankedStatus)
		if self.rankedStatus != rankedStatuses.NOT_SUBMITTED and self.rankedStatus != rankedStatuses.NEED_UPDATE and self.rankedStatus != rankedStatuses.UNKNOWN:
			# If the beatmap is updated and exists, the client needs more data
			data += "|{}|{}|{}\n{}\n{}\n{}\n".format(self.beatmapID, self.beatmapSetID, totalScores, self.offset, self.songName, self.rating)

		# Return the header
		return data

def convertRankedStatus(approvedStatus):
	"""
	Convert approved_status (from osu!api) to ranked status (for getscores)

	approvedStatus -- approved status, from osu!api
	return -- rankedStatus for getscores
	"""

	approvedStatus = int(approvedStatus)
	if (approvedStatus <= 0):
		return rankedStatuses.PENDING
	elif (approvedStatus == 1):
		return rankedStatuses.RANKED
	elif (approvedStatus == 2):
		return rankedStatuses.APPROVED
	elif (approvedStatus == 3):
		return rankedStatuses.QUALIFIED
	else:
		return rankedStatuses.UNKNOWN
