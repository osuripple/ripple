import tokenList
import uuid

class token:
	token = "";
	userID = 0;
	queue = bytes();


	def __init__(self, __userID, __token = None):
		"""Create a token object and set userID and token

		__userID -- user associated to this token
		__token -- 	if passed, set token to that value
					if not passed, token will be generated"""

		# Set userID
		self.userID = __userID

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
