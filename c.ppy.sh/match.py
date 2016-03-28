# TODO: Enqueue all
import gameModes
import matchScoringTypes
import matchTeamTypes
import matchModModes
import slotStatuses
import glob
import consoleHelper
import bcolors
import serverPackets
import dataTypes
import matchTeams

class match:
	"""Multiplayer match object"""
	matchID = 0
	inProgress = False
	mods = 0
	matchName = ""
	matchPassword = ""
	beatmapName = ""
	beatmapID = 0
	beatmapMD5 = ""
	slots = []	# list of dictionaries {"status": 0, "team": 0, "userID": -1, "mods": 0, "loaded": False, "skip": False, "complete": False}
	hostUserID = 0
	gameMode = gameModes.std
	matchScoringType = matchScoringTypes.score
	matchTeamType = matchTeamTypes.headToHead
	matchModMode = matchModModes.normal
	seed = 0

	def __init__(self, __matchID, __matchName, __matchPassword, __beatmapID, __beatmapName, __beatmapMD5, __gameMode, __hostUserID):
		"""
		Create a new match object

		__matchID -- match progressive identifier
		__matchName -- match name, string
		__matchPassword -- match md5 password. Leave empty for no password
		__beatmapID -- beatmap ID
		__beatmapName -- beatmap name, string
		__beatmapMD5 -- beatmap md5 hash, string
		__gameMode -- game mode ID. See gameModes.py
		__hostUserID -- user id of the host
		"""
		self.matchID = __matchID
		self.inProgress = False
		self.mods = 0
		self.matchName = __matchName
		self.matchPassword = __matchPassword
		self.beatmapID = __beatmapID
		self.beatmapName = __beatmapName
		self.beatmapMD5 = __beatmapMD5
		self.hostUserID = __hostUserID
		self.gameMode = __gameMode
		self.matchScoringTypes = matchScoringTypes.score	# default values
		self.matchTeamType = matchTeamTypes.headToHead		# default value
		self.matchModMode = matchModModes.normal			# default value
		self.seed = 0

		# Create all slots and reset them
		self.slots = []
		for _ in range(0,16):
			self.slots.append({"status": slotStatuses.free, "team": 0, "userID": -1, "mods": 0, "loaded": False, "skip": False, "complete": False})


	def getMatchData(self):
		"""
		Return binary match data structure for packetHelper
		"""
		# General match info
		struct = [
			[self.matchID, dataTypes.uInt16],
			[int(self.inProgress), dataTypes.byte],
			[0, dataTypes.byte],
			[self.mods, dataTypes.uInt32],
			[self.matchName, dataTypes.string],
			[self.matchPassword, dataTypes.string],
			[self.beatmapName, dataTypes.string],
			[self.beatmapID, dataTypes.uInt32],
			[self.beatmapMD5, dataTypes.string],
		]

		# Slots status IDs, always 16 elements
		for i in range(0,16):
			struct.append([self.slots[i]["status"], dataTypes.byte])

		# Slot teams, always 16 elements
		for i in range(0,16):
			struct.append([self.slots[i]["team"], dataTypes.byte])

		# Slot user ID. Write only if slot is occupied
		for i in range(0,16):
			uid = self.slots[i]["userID"]
			if (uid > -1):
				struct.append([uid, dataTypes.uInt32])

		# Other match data
		struct.extend([
			[self.hostUserID, dataTypes.sInt32],
			[self.gameMode, dataTypes.byte],
			[self.matchScoringType, dataTypes.byte],
			[self.matchTeamType, dataTypes.byte],
			[self.matchModMode, dataTypes.byte],
		])

		# Slot mods if free mod is enabled
		if (self.matchModMode == matchModModes.freeMod):
			for i in range(0,16):
				struct.append([self.slots[i]["mods"], dataTypes.uInt32])

		# Seed idk
		struct.append([self.seed, dataTypes.uInt32])

		return struct



	def setHost(self, newHost):
		"""
		Set room host to newHost and send him host packet

		newHost -- new host userID
		"""
		self.hostUserID = newHost

		# Send host packet to new host
		token = glob.tokens.getTokenFromUserID(newHost)
		if (token != None):
			token.enqueue(serverPackets.matchTransferHost())

		consoleHelper.printColored("> MPROOM{}: {} is now the host".format(self.matchID, newHost), bcolors.BLUE)

	def setSlot(self, slotID, slotStatus = None, slotTeam = None, slotUserID = None, slotMods = None, slotLoaded = None, slotSkip = None, slotComplete = None):
		"""
		Set a slot to a specific userID and status

		slotID -- id of that slot (0-15)
		slotStatus -- see slotStatuses.py
		slotTeam -- team id
		slotUserID -- user ID of user in that slot
		slotMods -- mods enabled in that slot. 0 if not free mod.
		slotLoaded -- loaded status True/False
		slotSkip -- skip status True/False
		slotComplete -- completed status True/False

		If Null is passed, that value won't be edited
		"""
		if (slotStatus != None):
			self.slots[slotID]["status"] = slotStatus

		if (slotTeam != None):
			self.slots[slotID]["team"] = slotTeam

		if (slotUserID != None):
			self.slots[slotID]["userID"] = slotUserID

		if (slotMods != None):
			self.slots[slotID]["mods"] = slotMods

		if (slotLoaded != None):
			self.slots[slotID]["loaded"] = slotLoaded

		if (slotSkip != None):
			self.slots[slotID]["skip"] = slotSkip

		if (slotComplete != None):
			self.slots[slotID]["complete"] = slotComplete


	def setSlotMods(self, slotID, mods):
		"""
		Set slotID mods. Same as calling setSlot and then sendUpdate

		slotID -- slot number
		mods -- new mods
		"""
		# Set new slot data and send update
		self.setSlot(slotID, None, None, None, mods)
		self.sendUpdate()
		consoleHelper.printColored("> MPROOM{}: Slot{} mods changed to {}".format(self.matchID, slotID, mods), bcolors.BLUE)


	def toggleSlotReady(self, slotID):
		"""
		Switch slotID ready/not ready status
		Same as calling setSlot and then sendUpdate

		slotID -- slot number
		"""
		# Update ready status and setnd update
		oldStatus = self.slots[slotID]["status"]
		if (oldStatus == slotStatuses.ready):
			newStatus = slotStatuses.notReady
		else:
			newStatus = slotStatuses.ready
		self.setSlot(slotID, newStatus, None, None, None)
		self.sendUpdate()
		consoleHelper.printColored("> MPROOM{}: Slot{} changed ready status to {}".format(self.matchID, slotID, self.slots[slotID]["status"]), bcolors.BLUE)

	def toggleSlotLock(self, slotID):
		"""
		Lock a slot
		Same as calling setSlot and then sendUpdate

		slotID -- slot number
		"""
		# Get token of user in that slot (if there's someone)
		if (self.slots[slotID]["userID"] > -1):
			token = glob.tokens.getTokenFromUserID(self.slots[slotID]["userID"])
		else:
			token = None

		# Check if slot is already locked
		if (self.slots[slotID]["status"] == slotStatuses.locked):
			newStatus = slotStatuses.free
		else:
			newStatus = slotStatuses.locked

		# Set new slot status
		self.setSlot(slotID, newStatus, 0, -1, 0)
		if (token != None):
			# Send updated settings to kicked user, so he returns to lobby
			token.enqueue(serverPackets.updateMatch(self.matchID))

		# Send updates to everyone else
		self.sendUpdate()
		consoleHelper.printColored("> MPROOM{}: Slot{} {}".format(self.matchID, slotID, "locked" if newStatus == slotStatuses.locked else "unlocked"), bcolors.BLUE)

	def playerLoaded(self, userID):
		"""
		Set a player loaded status to True

		userID -- ID of user
		"""
		slotID = self.getUserSlotID(userID)
		if (slotID == None):
			return

		# Set loaded to True
		self.slots[slotID]["loaded"] = True
		consoleHelper.printColored("> MPROOM{}: User {} loaded".format(self.matchID, userID), bcolors.BLUE)

		# Check all loaded
		total = 0
		loaded = 0
		for i in range(0,16):
			if (self.slots[i]["status"] == slotStatuses.playing):
				total+=1
				if (self.slots[i]["loaded"] == True):
					loaded+=1

		if (total == loaded):
			self.allPlayersLoaded()


	def allPlayersLoaded(self):
		"""Send allPlayersLoaded packet to every playing usr in match"""
		for i in range(0,16):
			if (self.slots[i]["userID"] > -1 and self.slots[i]["status"] == slotStatuses.playing):
				token = glob.tokens.getTokenFromUserID(self.slots[i]["userID"])
				if (token != None):
					token.enqueue(serverPackets.allPlayersLoaded())

		consoleHelper.printColored("> MPROOM{}: All players loaded! Corrispondere iniziare in 3...".format(self.matchID), bcolors.BLUE)


	def playerSkip(self, userID):
		"""
		Set a player skip status to True

		userID -- ID of user
		"""
		slotID = self.getUserSlotID(userID)
		if (slotID == None):
			return

		# Set skip to True
		self.slots[slotID]["skip"] = True
		consoleHelper.printColored("> MPROOM{}: User {} skipped".format(self.matchID, userID), bcolors.BLUE)

		# Send skip packet to every playing useR
		for i in range(0,16):
			uid = self.slots[i]["userID"]
			if (self.slots[i]["status"] == slotStatuses.playing and uid > -1):
				token = glob.tokens.getTokenFromUserID(uid)
				if (token != None):
					print("Enqueueueue {}".format(uid))
					token.enqueue(serverPackets.playerSkipped(uid))

		# Check all skipped
		total = 0
		skipped = 0
		for i in range(0,16):
			if (self.slots[i]["status"] == slotStatuses.playing):
				total+=1
				if (self.slots[i]["skip"] == True):
					skipped+=1

		if (total == skipped):
			self.allPlayersSkipped()

	def allPlayersSkipped(self):
		"""Send allPlayersSkipped packet to every playing usr in match"""
		for i in range(0,16):
			if (self.slots[i]["userID"] > -1 and self.slots[i]["status"] == slotStatuses.playing):
				token = glob.tokens.getTokenFromUserID(self.slots[i]["userID"])
				if (token != None):
					token.enqueue(serverPackets.allPlayersSkipped())

		consoleHelper.printColored("> MPROOM{}: All players skipped!".format(self.matchID), bcolors.BLUE)

	def playerCompleted(self, userID):
		"""
		Set userID's slot completed to True

		userID -- ID of user
		"""
		slotID = self.getUserSlotID(userID)
		if (slotID == None):
			return
		self.setSlot(slotID, None, None, None, None, None, None, True)

		# Console output
		consoleHelper.printColored("> MPROOM{}: User {} has completed".format(self.matchID, userID), bcolors.BLUE)

		# Check all completed
		total = 0
		completed = 0
		for i in range(0,16):
			if (self.slots[i]["status"] == slotStatuses.playing):
				total+=1
				if (self.slots[i]["complete"] == True):
					completed+=1

		if (total == completed):
			self.allPlayersCompleted()

	def allPlayersCompleted(self):
		"""Cleanup match stuff and send match end packet to everyone"""

		# Reset inProgress
		self.inProgress = False

		# Reset slots
		for i in range(0,16):
			if (self.slots[i]["userID"] > -1 and self.slots[i]["status"] == slotStatuses.playing):
				self.slots[i]["status"] = slotStatuses.notReady
				self.slots[i]["loaded"] = False
				self.slots[i]["skip"] = False
				self.slots[i]["complete"] = False

		# Send match update
		self.sendUpdate()

		# Send match complete
		for i in range(0,16):
			if (self.slots[i]["userID"] > -1):
				token = glob.tokens.getTokenFromUserID(self.slots[i]["userID"])
				if (token != None):
					token.enqueue(serverPackets.matchComplete())

		# Console output
		consoleHelper.printColored("> MPROOM{}: Match completed".format(self.matchID), bcolors.BLUE)



	def getUserSlotID(self, userID):
		"""
		Get slot ID occupied by userID

		return -- slot id if found, None if user is not in room
		"""

		for i in range(0,16):
			if (self.slots[i]["userID"] == userID):
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
			if (self.slots[i]["status"] == slotStatuses.free):
				# Occupy slot
				self.setSlot(i, slotStatuses.notReady, 0, userID, 0)

				# Send updated match data
				self.sendUpdate()

				# Console output
				consoleHelper.printColored("> MPROOM{}: {} joined the room".format(self.matchID, userID), bcolors.BLUE)

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
		self.setSlot(slotID, slotStatuses.free, 0, -1, 0)

		# Check if everyone left
		if (self.countUsers() == 0):
			# Dispose match
			glob.matches.disposeMatch(self.matchID)
			consoleHelper.printColored("> MPROOM{}: Room disposed".format(self.matchID), bcolors.BLUE)
			return

		# Check if host left
		if (userID == self.hostUserID):
			# Give host to someone else
			for i in range(0,16):
				uid = self.slots[i]["userID"]
				if (uid > -1):
					self.setHost(uid)
					break

		# Send updated match data
		self.sendUpdate()

		# Console output
		consoleHelper.printColored("> MPROOM{}: {} left the room".format(self.matchID, userID), bcolors.BLUE)


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

		# Make sure there is no one inside new slot
		if (self.slots[newSlotID]["userID"] > -1):
			return

		# Get old slot data
		oldData = self.slots[oldSlotID].copy()

		# Free old slot
		self.setSlot(oldSlotID, slotStatuses.free, 0, -1, 0)

		# Occupy new slot
		self.setSlot(newSlotID, oldData["status"], oldData["team"], userID, oldData["mods"])

		# Send updated match data
		self.sendUpdate()

		# Console output
		consoleHelper.printColored("> MPROOM{}: {} moved to slot {}".format(self.matchID, userID, newSlotID), bcolors.BLUE)

	def changePassword(self, newPassword):
		"""
		Change match password to newPassword

		newPassword -- new password string
		"""
		self.matchPassword = newPassword

		# Send password change to every user in match
		for i in range(0,16):
			if (self.slots[i]["userID"] > -1):
				token = glob.tokens.getTokenFromUserID(self.slots[i]["userID"])
				if (token != None):
					token.enqueue(serverPackets.changeMatchPassword(self.matchPassword))

		# Send new match settings too
		self.sendUpdate()

		# Console output
		consoleHelper.printColored("> MPROOM{}: Password changed to {}".format(self.matchID, self.matchPassword), bcolors.BLUE)


	def changeMatchMods(self, mods):
		"""
		Set match global mods

		mods -- mods bitwise int thing
		"""
		# Set new mods and send update
		self.mods = mods
		self.sendUpdate()
		consoleHelper.printColored("> MPROOM{}: Mods changed to {}".format(self.matchID, self.mods), bcolors.BLUE)

	def userHasBeatmap(self, userID, has = True):
		"""
		Set no beatmap status for userID

		userID -- ID of user
		has -- True if has beatmap, false if not
		"""
		# Make sure the user is in room
		slotID = self.getUserSlotID(userID)
		if (slotID == None):
			return

		# Set slot
		self.setSlot(slotID, slotStatuses.noMap if not has else slotStatuses.notReady)

		# Send updates
		self.sendUpdate()

	def transferHost(self, slotID):
		"""
		Transfer host to slotID

		slotID -- ID of slot
		"""
		# Make sure there is someone in that slot
		uid = self.slots[slotID]["userID"]
		if (uid == -1):
			return

		# Transfer host
		self.setHost(uid)

		# Send updates
		self.sendUpdate()


	def playerFailed(self, userID):
		"""
		Send userID's failed packet to everyone in match

		userID -- ID of user
		"""
		# Make sure the user is in room
		slotID = self.getUserSlotID(userID)
		if (slotID == None):
			return

		# Send packet to everyone
		for i in range(0,16):
			uid = self.slots[i]["userID"]
			if (uid > -1):
				token = glob.tokens.getTokenFromUserID(uid)
				if (token != None):
					token.enqueue(serverPackets.playerFailed(slotID))

		# Console output
		consoleHelper.printColored("> MPROOM{}: {} has failed!".format(self.matchID, userID), bcolors.BLUE)


	def invite(self, fro, to):
		"""
		Fro invites to in this match.

		fro -- sender userID
		to -- receiver userID
		"""

		# Get tokens
		froToken = glob.tokens.getTokenFromUserID(fro)
		toToken = glob.tokens.getTokenFromUserID(to)
		if (froToken == None or toToken == None):
			return

		# FokaBot is too busy
		if (to == 999):
			froToken.enqueue(serverPackets.sendMessage("FokaBot", froToken.username, "I would love to join your match, but I'm busy keeping ripple up and running. Sorry. Beep Boop."))

		# Send message
		message = "Come join my multiplayer match: \"[osump://{}/{} {}]\"".format(self.matchID, self.matchPassword.replace(" ", "_"), self.matchName)
		toToken.enqueue(serverPackets.sendMessage(froToken.username, toToken.username, message))


	def countUsers(self):
		"""
		Return how many players are in that match

		return -- number of users
		"""

		c = 0
		for i in range(0,16):
			if (self.slots[i]["userID"] > -1):
				c+=1

		return c

	def changeTeam(self, userID):
		"""
		Change userID's team

		userID -- id of user
		"""
		# Make sure the user is in room
		slotID = self.getUserSlotID(userID)
		if (slotID == None):
			return

		# Update slot and send update
		newTeam = matchTeams.blue if self.slots[slotID]["team"] == matchTeams.red else matchTeams.red
		self.setSlot(slotID, None, newTeam)
		self.sendUpdate()



	def sendUpdate(self):
		# Send to users in room
		for i in range(0,16):
			if (self.slots[i]["userID"] > -1):
				token = glob.tokens.getTokenFromUserID(self.slots[i]["userID"])
				if (token != None):
					token.enqueue(serverPackets.updateMatch(self.matchID))

		# Send to users in lobby
		for i in glob.matches.usersInLobby:
			token = glob.tokens.getTokenFromUserID(i)
			if (token != None):
				token.enqueue(serverPackets.updateMatch(self.matchID))

	def checkTeams(self):
		"""
		Check if match teams are valid

		return -- True if valid, False if invalid
		"""
		if (match.matchTeamType != matchTeamTypes.teamVs or matchTeamTypes != matchTeamTypes.tagTeamVs):
			# Teams are always valid if we have no teams
			return True

		# We have teams, check if they are valid
