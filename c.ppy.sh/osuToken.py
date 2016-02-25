import tokenList
import uuid
import actions
import gameModes
import userHelper
import channelList

class token:
	token = ""
	userID = 0
	username = ""
	actionID = actions.idle
	actionText = ""
	actionMd5 = ""
	gameMode = gameModes.std

	location = [0,0]

	queue = bytes()
	joinedChannels = []


	def __init__(self, __userID, __token = None):
		"""Create a token object and set userID and token

		__userID -- user associated to this token
		__token -- 	if passed, set token to that value
					if not passed, token will be generated"""

		# Set userID and username
		self.userID = __userID
		self.username = userHelper.getUserUsername(self.userID)

		# Generate/set token
		if (__token != None):
			self.token = __token
		else:
			self.token = str(uuid.uuid4())


	def enqueue(self, __bytes):
		"""Add bytes (packets) to queue

		__bytes -- (packet) bytes to enqueue"""
		self.queue += __bytes


	def resetQueue(self):
		"""Resets the queue. Call when enqueued packets have been sent"""
		self.queue = bytes()


	def joinChannel(self, __channel):
		"""Add __channel to joined channels list

		__channel -- channel name"""

		if (__channel not in self.joinedChannels):
			self.joinedChannels.append(__channel)


	def partChannel(self, __channel):
		"""Remove __channel from joined channels list

		__channel -- channel name"""
		if (__channel in self.joinedChannels):
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
