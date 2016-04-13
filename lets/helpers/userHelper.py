from lets import glob

def getUserID(username):
	"""
	Get username's user ID

	username -- user
	return -- user id or 0
	"""

	# Get user ID from db
	userID = glob.db.fetch("SELECT id FROM users WHERE username = ?", [username])

	# Make sure the query returned something
	if (userID == None):
		return 0

	# Return user ID
	return userID["id"]
