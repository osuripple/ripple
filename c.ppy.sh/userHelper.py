import passwordHelper

def getUserID(__db, __username):
	# Get user ID from db
	userID = __db.fetch("SELECT osu_id FROM users WHERE username = ?", [__username])

	# Make sure the query returned something
	if (userID == None):
		return False

	# Return user ID
	return userID["osu_id"]

def checkLogin(__db, __userID, __password):
	# Get password data
	passwordData = __db.fetch("SELECT password_md5, salt FROM users WHERE osu_id = ?", [__userID])

	# Make sure the query returned something
	if (passwordData == None):
		return False

	# Return password valid/invalid
	return passwordHelper.checkPassword(__password, passwordData["salt"], passwordData["password_md5"])
