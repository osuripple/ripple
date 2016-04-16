import uuid
import actions
import gameModes
import userHelper
import time
import consoleHelper
import bcolors
import serverPackets
import logoutEvent

class token:
	"""Osu Token object

	token -- token string
	userID -- userID associated to that token
	username -- username relative to userID (cache)
	rank -- rank (permissions) relative to userID (cache)
	actionID -- current user action (see actions.py)
	actionText -- current user action text
	actionMd5 -- md5 relative to user action
	actionMods -- current acton mods
	gameMode -- current user game mode
	location -- [latitude,longitude]
	queue -- packets queue
	joinedChannels -- list. Contains joined channel names
	spectating -- userID of spectating user. 0 if not spectating.
	spectators -- list. Contains userIDs of spectators
	country -- osu country code. Use countryHelper to convert from letter country code to osu country code
	pingTime -- latest packet received UNIX time
	loginTime -- login UNIX time
	"""

	token = ""
	userID = 0
	username = ""
	rank = 0
	actionID = actions.idle
	actionText = ""
	actionMd5 = ""
	actionMods = 0
	gameMode = gameModes.std

	country = 0
	location = [0,0]

	queue = bytes()
	joinedChannels = []

	spectating = 0
	spectators = []

	pingTime = 0
	loginTime = 0

	awayMessage = ""

	matchID = -1


	def __init__(self, __userID, __token = None):
		"""
		Create a token object and set userID and token

		__userID -- user associated to this token
		__token -- 	if passed, set token to that value
					if not passed, token will be generated
		"""

		# Set stuff
		self.userID = __userID
		self.username = userHelper.getUsername(self.userID)
		self.rank = userHelper.getRankPrivileges(self.userID)
		self.loginTime = int(time.time())
		self.pingTime = self.loginTime

		# Default variables
		self.spectators = []
		self.spectating = 0
		self.location = [0,0]
		self.joinedChannels = []
		self.actionID = actions.idle
		self.actionText = ""
		self.actionMods = 0
		self.gameMode = gameModes.std
		self.awayMessage = ""
		self.matchID = -1

		# Generate/set token
		if __token != None:
			self.token = __token
		else:
			self.token = str(uuid.uuid4())


	def enqueue(self, __bytes):
		"""
		Add bytes (packets) to queue

		__bytes -- (packet) bytes to enqueue
		"""

		self.queue += __bytes


	def resetQueue(self):
		"""Resets the queue. Call when enqueued packets have been sent"""
		self.queue = bytes()


	def joinChannel(self, __channel):
		"""Add __channel to joined channels list

		__channel -- channel name"""

		if __channel not in self.joinedChannels:
			self.joinedChannels.append(__channel)


	def partChannel(self, __channel):
		"""Remove __channel from joined channels list

		__channel -- channel name"""

		if __channel in self.joinedChannels:
			self.joinedChannels.remove(__channel)


	def setLocation(self, __location):
		"""Set location (latitude and longitude)

		__location -- [latitude, longitude]"""

		self.location = __location


	def getLatitude(self):
		"""Get latitude

		return -- latitude"""

		return self.location[0]


	def getLongitude(self):
		"""Get longitude

		return -- longitude"""
		return self.location[1]


	def startSpectating(self, __userID):
		"""Set the spectating user to __userID

		__userID -- target userID"""
		self.spectating = __userID


	def stopSpectating(self):
		"""Set the spectating user to 0, aka no user"""
		self.spectating = 0


	def addSpectator(self, __userID):
		"""Add __userID to our spectators

		userID -- new spectator userID"""

		# Add userID to spectators if not already in
		if __userID not in self.spectators:
			self.spectators.append(__userID)


	def removeSpectator(self, __userID):
		"""Remove __userID from our spectators

		userID -- old spectator userID"""

		# Remove spectator
		if __userID in self.spectators:
			self.spectators.remove(__userID)


	def setCountry(self, __countryID):
		"""Set country to __countryID

		__countryID -- numeric country ID. See countryHelper.py"""

		self.country = __countryID


	def getCountry(self):
		"""Get numeric country ID

		return -- numeric country ID. See countryHelper.py"""

		return self.country


	def updatePingTime(self):
		"""Update latest ping time"""
		self.pingTime = int(time.time())

	def setAwayMessage(self, __awayMessage):
		"""Set a new away message"""
		self.awayMessage = __awayMessage

	def joinMatch(self, __matchID):
		"""
		Set match to matchID

		__matchID -- new match ID
		"""
		self.matchID = __matchID

	def partMatch(self):
		"""Set match to -1"""
		self.matchID = -1

	def kick(self):
		"""Kick this user from the server"""
		# Send packet to target
		consoleHelper.printColored("> {} has been disconnected. (kick)".format(self.username), bcolors.YELLOW)
		self.enqueue(serverPackets.notification("You have been kicked from the server. Please login again."))
		self.enqueue(serverPackets.loginFailed())

		# Logout event
		logoutEvent.handle(self, None)
