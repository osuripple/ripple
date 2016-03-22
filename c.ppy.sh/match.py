import gameModes
import matchScoringTypes
import matchTeamTypes
import matchModModes
import slotStatuses
import glob
import consoleHelper
import bcolors
import serverPackets

class match:
	"""Multiplayer match object"""
	matchID = 0
	inProgress = False
	#matchType = 0
	mods = 0
	matchName = ""
	matchPassword = ""
	beatmapName = ""
	beatmapID = 0
	beatmapMD5 = ""

	slotStatus = []
	slotTeam = []
	slotUserID = []
	slotMod = []
	for i in range(0,16):
		slotStatus.append(slotStatuses.free)
		slotTeam.append(0)
		slotUserID.append(0)
		slotMod.append(0)

	hostUserID = 0
	gameMode = gameModes.std
	matchScoringType = matchScoringTypes.score
	matchTeamType = matchTeamTypes.headToHead
	matchModMode = matchModModes.normal
	seed = 0

	def __init__(self, __matchID, __matchName, __matchPassword, __beatmapID, __beatmapName, __beatmapMD5, __gameMode, __seed):
		"""
		Create a new match object

		__matchID -- match progressive identifier
		__matchName -- match name, string
		__matchPassword -- match md5 password. Leave empty for no password
		__beatmapID -- beatmap ID
		__beatmapName -- beatmap name, string
		__beatmapMD5 -- beatmap md5 hash, string
		__gameMode -- game mode ID. See gameModes.py
		__seed -- idk, int
		"""
		self.matchID = __matchID
		self.inProgress = False
		#self.matchType = 0		# not used tho
		self.mods = 0
		self.matchName = __matchName
		self.matchPassword = __matchPassword
		self.beatmapID = __beatmapID
		self.beatmapName = __beatmapName
		self.beatmapMD5 = __beatmapMD5
		self.hostUserID = 0		# set it manually
		self.gameMode = __gameMode
		self.matchScoringTypes = matchScoringTypes.score	# default values
		self.matchTeamType = matchTeamTypes.headToHead		# default value
		self.matchModMode = matchModModes.normal			# default value
		self.seed = __seed

		# Create all slots and reset them
		self.slotStatus = []
		self.slotTeam = []
		self.slotUserID = []
		self.slotMod = []
		for _ in range(0,16):
			self.slotStatus.append(slotStatuses.free)
			self.slotTeam.append(0)
			self.slotUserID.append(0)
			self.slotMod.append(0)

		# Move host to slot 1 and give him room host
		#self.userJoin(__hostUserID)
		#self.setHost(__hostUserID)

		#self.slotStatus[0] = slotStatuses.notReady	# idk
		#self.slotUserID[0] = __hostUserID

		# TODO: Lock unused slots
		#for i in range(2,16):
			#self.slotStatus[i] = slotStatuses.locked

	def setHost(self, newHost):
		"""
		Set room host to newHost

		newHost -- new host userID
		"""

		self.hostUserID = newHost

	def setSlot(self, slotID, userID, slotStatus):
		"""
		Set a slot to a specific userID and status

		userID -- user id of user in that slot
		slotStatus -- see slotStatuses.py
		"""

		self.slotUserID[slotID] = userID
		self.slotStatus[slotID] = slotStatus

	def getUserSlotID(self, userID):
		"""
		Get slot ID occupied by userID

		return -- slot id if found, None if user is not in room
		"""

		for i in range(0,16):
			if (self.slotUserID[i] == userID):
				return i

		return None

	def userJoin(self, userID):
		"""
		Add someone to users in match

		userID -- user id of the user
		return -- True if join success, False if fail (room is full)
		"""

		# Find first free slot
		for i in range(0,16):
			if (self.slotStatus[i] == slotStatuses.free):
				self.setSlot(i, userID, slotStatuses.notReady)
				return True

		return False

	def userLeft(self, userID):
		"""
		Remove someone from users in match

		userID -- user if of the user
		"""

		# Make sure the user is in room
		slotID = self.getUserSlotID(userID)
		if (slotID == None):
			return

		# Set that slot to free
		self.setSlot(slotID, 0, slotStatuses.free)

		# Check if everyone left
		if (self.countUsers() == 0):
			# Dispose match
			glob.matches.disposeMatch(self.matchID)
			consoleHelper.printColored("> MPROOM{}: Room disposed".format(self.matchID), bcolors.BLUE)

		# Check if host left
		if (userID == self.hostUserID):
			# Give host to someone else
			pass


	def userChangeSlot(self, userID, newSlotID):
		"""
		Change userID slot to newSlotID

		userID -- user that changed slot
		newSlotID -- slot id of new slot
		"""

		# Make sure the user is in room
		oldSlotID = self.getUserSlotID(userID)
		if (oldSlotID == None):
			return

		# Get current user status
		oldStatus = self.slotStatus[oldSlotID]

		# Free old slot
		self.setSlot(oldSlotID, 0, slotStatuses.free)

		# Occupy new slot
		self.setSlot(newSlotID, userID, oldStatus)


	def countUsers(self):
		"""
		Return how many players are in that match

		return -- number of users
		"""

		c = 0
		for i in self.slotUserID:
			if (i != 0):
				c+=1

		return c

	def sendUpdate(self):
		# Room users
		for i in self.slotUserID:
			if (i != 0):
				token = glob.tokens.getTokenFromUserID(i)
				if (token != None):
					token.enqueue(serverPackets.matchSettings(self.matchID, True))

		# Lobby users
		for i in glob.matches.usersInLobby:
			token.enqueue(serverPackets.matchSettings(self.matchID, True))
