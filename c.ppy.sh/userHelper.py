import passwordHelper
import gameModes
import glob

def getUserID(username):
	"""Get username's user ID

	db -- database connection
	username -- user

	return -- user id or false"""

	# Get user ID from db
	userID = glob.db.fetch("SELECT osu_id FROM users WHERE username = ?", [username])

	# Make sure the query returned something
	if (userID == None):
		return False

	# Return user ID
	return userID["osu_id"]


def checkLogin(userID, password):
	"""Check userID's login with specified password

	db -- database connection
	userID -- user id
	password -- plain md5 password

	return -- true or false"""

	# Get password data
	passwordData = glob.db.fetch("SELECT password_md5, salt FROM users WHERE osu_id = ?", [userID])

	# Make sure the query returned something
	if (passwordData == None):
		return False

	# Return password valid/invalid
	return passwordHelper.checkPassword(password, passwordData["salt"], passwordData["password_md5"])


# TODO: User exists function


def getUserAllowed(userID):
	"""Get allowed status for userID

	db -- database connection
	userID -- user ID

	return -- allowed int"""

	return glob.db.fetch("SELECT allowed FROM users WHERE osu_id = ?", [userID])["allowed"]


def getUserRank(userID):
	"""This returns rank (PRIVILEGES), not game rank (like #1337)
	If you want to get that rank, user getUserGameRank instead"""
	return glob.db.fetch("SELECT rank FROM users WHERE osu_id = ?", [userID])["rank"]


def getUserSilenceEnd(userID):
	"""Remember to subtract time.time() to get the actual silence time"""
	return glob.db.fetch("SELECT silence_end FROM users WHERE osu_id = ?", [userID])["silence_end"]


def getUserRankedScore(userID, gameMode):
	modeForDB = gameModes.getGameModeForDB(gameMode)
	return glob.db.fetch("SELECT ranked_score_"+modeForDB+" FROM users_stats WHERE osu_id = ?", [userID])["ranked_score_"+modeForDB]


def getUserTotalScore(userID, gameMode):
	modeForDB = gameModes.getGameModeForDB(gameMode)
	return glob.db.fetch("SELECT total_score_"+modeForDB+" FROM users_stats WHERE osu_id = ?", [userID])["total_score_"+modeForDB]


def getUserAccuracy(userID, gameMode):
	modeForDB = gameModes.getGameModeForDB(gameMode)
	return glob.db.fetch("SELECT avg_accuracy_"+modeForDB+" FROM users_stats WHERE osu_id = ?", [userID])["avg_accuracy_"+modeForDB]


def getUserGameRank(userID, gameMode):
	modeForDB = gameModes.getGameModeForDB(gameMode)
	return glob.db.fetch("SELECT position FROM leaderboard_"+modeForDB+" WHERE user = ?", [userID])["position"]


def getUserPlaycount(userID, gameMode):
	modeForDB = gameModes.getGameModeForDB(gameMode)
	return glob.db.fetch("SELECT playcount_"+modeForDB+" FROM users_stats WHERE osu_id = ?", [userID])["playcount_"+modeForDB]
