import gameModes
import matchScoringTypes
import matchTeamTypes
import matchModModes
import slotStatuses

class match:
	"""Multiplayer match object"""
	matchID = 1337
	inProgress = False
	matchType = 0
	mods = 0
	matchName = ""
	matchPassword = ""
	beatmapName = ""
	beatmapID = 0
	beatmapMD5 = ""

	slotStatus = []
	slotTeam = []
	slotUserID = []
	for i in range(0,15):
		slotStatus.append(slotStatuses.free)
		slotTeam.append(0)
		slotUserID.append(0)

	hostUserID = 0
	gameMode = gameModes.std
	matchScoringType = matchScoringTypes.score
	matchTeamType = matchTeamTypes.headToHead
	matchModMode = matchModModes.normal
	seed = 0

	def __init__(self, __matchID, __matchName, __matchPassword, __beatmapID, __beatmapName, __beatmapMD5, __hostUserID, __seed):
		"""
		Create a new match object

		__matchID -- match progressive identifier
		__matchName -- match name, string
		__matchPassword -- match md5 password. Leave empty for no password
		__beatmapID -- beatmap ID
		__beatmapName -- beatmap name, string
		__beatmapMD5 -- beatmap md5 hash, string
		__hostUserID -- user ID who has the host
		__seed -- idk, int
		"""
		# TODO: Game mode
		self.matchID = __matchID
		self.inProgress = False
		self.matchType = 0
		self.mods = 0
		self.matchName = __matchName
		self.matchPassword = __matchPassword
		self.beatmapID = __beatmapID
		self.beatmapName = __beatmapName
		self.beatmapMD5 = __beatmapMD5
		self.hostUserID = __hostUserID
		self.gameMode = gameModes.std
		self.matchScoringTypes = matchScoringTypes.score
		self.matchTeamType = matchTeamTypes.headToHead
		self.matchModMode = matchModModes.normal
		self.seed = __seed

		# Create all slots and reset them
		self.slotStatus = []
		self.slotTeam = []
		self.slotUserID = []
		for _ in range(0,15):
			self.slotStatus.append(slotStatuses.free)
			self.slotTeam.append(0)
			self.slotUserID.append(0)

		# Move host to slot 1
		self.slotStatus[0] = 4	# idk
		self.slotUserID[0] = __hostUserID

		# TODO: Lock unused slots
