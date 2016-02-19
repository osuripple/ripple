import osuToken

class tokenList:
	# Connected users
	# Contains token objects
	tokens = {}

	def addToken(self, __userID):
		"""Add a token object to tokens list

		__userID -- user id associated to that token"""
		newToken = osuToken.token(__userID)
		self.tokens[newToken.token] = newToken


	def getUserIDFromToken(self, __token):
		"""Get user ID from a token

		__token -- token to find

		return: false if not found, userID if found"""

		# Make sure the token exists
		if (__token not in self.tokens):
			return False

		# Get userID associated to that token
		return self.tokens[self.tokens.index(__token)].userID;

	def deleteOldTokens(self, __userID):
		"""Delete old userID's tokens if found

		userID -- tokens associated to this user will be deleted"""

		# Delete older tokens
		for key, value in self.tokens.items():
			if (value.userID == __userID):
				self.tokens.pop(i)
