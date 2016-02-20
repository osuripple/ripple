import osuToken

class tokenList:
	# Connected users
	# Index: token string
	# Value: token object
	tokens = {}

	def addToken(self, __userID):
		"""Add a token object to tokens list

		__userID -- user id associated to that token"""

		newToken = osuToken.token(__userID)
		self.tokens[newToken.token] = newToken
		return newToken


	def getUserIDFromToken(self, __token):
		"""Get user ID from a token

		__token -- token to find

		return: false if not found, userID if found"""

		# Make sure the token exists
		if (__token not in self.tokens):
			return False

		# Get userID associated to that token
		return self.tokens[self.tokens.index(__token)].userID;


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


	def deleteOldTokens(self, __userID):
		"""Delete old userID's tokens if found

		userID -- tokens associated to this user will be deleted"""

		# Delete older tokens
		for key, value in self.tokens.items():
			if (value.userID == __userID):
				# Delete this token from the dictionary
				self.tokens.pop(key)

				# break or items() function throws errors
				break
