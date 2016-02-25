import osuToken
import userHelper

class tokenList:
	# Connected users
	# Index: token string
	# Value: token object
	tokens = {}

	def addToken(self, __userID):
		"""Add a token object to tokens list

		__userID -- user id associated to that token

		return -- token object"""

		newToken = osuToken.token(__userID)
		self.tokens[newToken.token] = newToken
		return newToken

	def fakeUsers(self):
		for i in range (1000,1041):
			if (userHelper.userExists(i) == True):
				self.addToken(i)

	def deleteToken(self, __token):
		"""Delete a token from token list

		__token -- token string"""

		self.tokens.pop(__token)


	def getUserIDFromToken(self, __token):
		"""Get user ID from a token

		__token -- token to find

		return: false if not found, userID if found"""

		# Make sure the token exists
		if (__token not in self.tokens):
			return False

		# Get userID associated to that token
		return self.tokens[__token].userID;
		#return self.tokens[self.tokens.index(__token)].userID;


	def getTokenFromUserID(self, __userID):
		"""Get token from a user ID

		__userID -- user ID to find

		return: false if not found, token object if found"""

		# Make sure the token exists
		for key, value in self.tokens.items():
			if (value.userID == __userID):
				return value

		# Return none if not found
		return None


	def getTokenFromUsername(self, __username):
		"""Get token from a username

		__username -- username to find

		return: false if not found, token object if found"""

		# Make sure the token exists
		for key, value in self.tokens.items():
			if (value.username == __username):
				return value

		# Return none if not found
		return None

	def deleteOldTokens(self, __userID):
		"""Delete old userID's tokens if found

		__userID -- tokens associated to this user will be deleted"""

		# Delete older tokens
		for key, value in self.tokens.items():
			if (value.userID == __userID):
				# Delete this token from the dictionary
				self.tokens.pop(key)

				# break or items() function throws errors
				break

	def multipleEnqueue(self, __packet, __who, __but = False):
		""" Enqueue a packet to multiple users

		__packet -- packet bytes to enqueue
		__who -- userIDs array
		__but -- if True, will enqueue to everyone but users in __who array"""

		for key, value in self.tokens.items():
			shouldEnqueue = False
			if (value.userID in __who and not __but):
				shouldEnqueue = True
			elif (value.userID not in __who and __but):
				shouldEnqueue = True

			if (shouldEnqueue):
				value.enqueue(__packet)



	def enqueueAll(self, __packet):
		"""Enqueue packet(s) to every connected user

		__packet -- packet bytes to enqueue"""

		for key, value in self.tokens.items():
			value.enqueue(__packet)
