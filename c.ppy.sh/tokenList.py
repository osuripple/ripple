import osuToken
import time
import threading
import consoleHelper
import bcolors
import serverPackets

class tokenList:
	"""
	List of connected osu tokens

	tokens -- dictionary. key: token string, value: token object
	"""

	tokens = {}

	def addToken(self, __userID):
		"""
		Add a token object to tokens list

		__userID -- user id associated to that token
		return -- token object
		"""

		newToken = osuToken.token(__userID)
		self.tokens[newToken.token] = newToken
		return newToken

	def deleteToken(self, __token):
		"""
		Delete a token from token list if it exists

		__token -- token string
		"""

		if (__token in self.tokens):
			self.tokens.pop(__token)


	def getUserIDFromToken(self, __token):
		"""
		Get user ID from a token

		__token -- token to find

		return: false if not found, userID if found
		"""

		# Make sure the token exists
		if (__token not in self.tokens):
			return False

		# Get userID associated to that token
		return self.tokens[__token].userID


	def getTokenFromUserID(self, __userID):
		"""
		Get token from a user ID

		__userID -- user ID to find
		return -- False if not found, token object if found
		"""

		# Make sure the token exists
		for _, value in self.tokens.items():
			if (value.userID == __userID):
				return value

		# Return none if not found
		return None


	def getTokenFromUsername(self, __username):
		"""
		Get token from a username

		__username -- username to find
		return -- False if not found, token object if found
		"""

		# lowercase
		who  = __username.lower()

		# Make sure the token exists
		for _, value in self.tokens.items():
			if (value.username.lower() == who):
				return value

		# Return none if not found
		return None


	def deleteOldTokens(self, __userID):
		"""
		Delete old userID's tokens if found

		__userID -- tokens associated to this user will be deleted
		"""

		# Delete older tokens
		for key, value in self.tokens.items():
			if (value.userID == __userID):
				# Delete this token from the dictionary
				self.tokens.pop(key)

				# break or items() function throws errors
				break


	def multipleEnqueue(self, __packet, __who, __but = False):
		"""
		Enqueue a packet to multiple users

		__packet -- packet bytes to enqueue
		__who -- userIDs array
		__but -- if True, enqueue to everyone but users in __who array
		"""

		for _, value in self.tokens.items():
			shouldEnqueue = False
			if (value.userID in __who and not __but):
				shouldEnqueue = True
			elif (value.userID not in __who and __but):
				shouldEnqueue = True

			if (shouldEnqueue):
				value.enqueue(__packet)



	def enqueueAll(self, __packet):
		"""
		Enqueue packet(s) to every connected user

		__packet -- packet bytes to enqueue
		"""

		for _, value in self.tokens.items():
			value.enqueue(__packet)

	def usersTimeoutCheckLoop(self, __timeoutTime = 100, __checkTime = 100):
		"""
		Deletes all timed out users.
		If called once, will recall after __checkTime seconds and so on, forever
		CALL THIS FUNCTION ONLY ONCE!

		__timeoutTime - seconds of inactivity required to disconnect someone (Default: 100)
		__checkTime - seconds between loops (Default: 100)
		"""

		timedOutTokens = []		# timed out users
		timeoutLimit = time.time()-__timeoutTime
		for key, value in self.tokens.items():
			# Check timeout (fokabot is ignored)
			if (value.pingTime < timeoutLimit and value.userID != 999):
				# That user has timed out, add to disconnected tokens
				# We can't delete it while iterating or items() throws an error
				timedOutTokens.append(key)

				# Send logout packet to everyone
				# TODO: Move to event handler
				self.enqueueAll(serverPackets.userLogout(value.userID))

				# Console output
				consoleHelper.printColored("> {} have been disconnected (timeout)".format(value.username), bcolors.YELLOW)

		# Delete timed out users from self.tokens
		# i is token string (dictionary key)
		for i in timedOutTokens:
			self.tokens.pop(i)

		# Schedule a new check (endless loop)
		threading.Timer(__checkTime, self.usersTimeoutCheckLoop, [__timeoutTime, __checkTime]).start()
