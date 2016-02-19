import passwordHelper

def getUserID(db, username):
	"""Get username's user ID

	db -- database connection
	username -- user

	return -- user id or false"""

	# Get user ID from db
	userID = db.fetch("SELECT osu_id FROM users WHERE username = ?", [username])

	# Make sure the query returned something
	if (userID == None):
		return False

	# Return user ID
	return userID["osu_id"]


def checkLogin(db, userID, password):
	"""Check userID's login with specified password

	db -- database connection
	userID -- user id
	password -- plain md5 password

	return -- true or false"""

	# Get password data
	passwordData = db.fetch("SELECT password_md5, salt FROM users WHERE osu_id = ?", [userID])

	# Make sure the query returned something
	if (passwordData == None):
		return False

	# Return password valid/invalid
	return passwordHelper.checkPassword(password, passwordData["salt"], passwordData["password_md5"])


# TODO: User exists function


def getUserAllowed(db, userID):
	"""Get allowed status for userID

	db -- database connection
	userID -- user ID

	return -- allowed int"""

	# Return user ID
	return db.fetch("SELECT allowed FROM users WHERE osu_id = ?", [userID])["allowed"]
