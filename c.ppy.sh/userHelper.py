import passwordHelper
import gameModes
import glob

def getUserID(username):
	"""
	Get username's user ID

	db -- database connection
	username -- user
	return -- user id or False
	"""

	# Get user ID from db
	userID = glob.db.fetch("SELECT osu_id FROM users WHERE username = ?", [username])

	# Make sure the query returned something
	if (userID == None):
		return False

	# Return user ID
	return userID["osu_id"]


def checkLogin(userID, password):
	"""
	Check userID's login with specified password

	db -- database connection
	userID -- user id
	password -- plain md5 password
	return -- True or False
	"""

	# Get password data
	passwordData = glob.db.fetch("SELECT password_md5, salt FROM users WHERE osu_id = ?", [userID])

	# Make sure the query returned something
	if (passwordData == None):
		return False

	# Return password valid/invalid
	return passwordHelper.checkPassword(password, passwordData["salt"], passwordData["password_md5"])


def userExists(userID):
	"""
	Check if userID exists

	userID -- user ID to check
	return -- bool
	"""

	result = glob.db.fetch("SELECT id FROM users WHERE osu_id = ?", [userID])
	if (result == None):
		return False
	else:
		return True


def getUserAllowed(userID):
	"""
	Get allowed status for userID

	db -- database connection
	userID -- user ID
	return -- allowed int
	"""

	return glob.db.fetch("SELECT allowed FROM users WHERE osu_id = ?", [userID])["allowed"]


def getUserRank(userID):
	"""
	This returns rank **(PRIVILEGES)**, not game rank (like #1337)
	If you want to get that rank, user getUserGameRank instead
	"""

	return glob.db.fetch("SELECT rank FROM users WHERE osu_id = ?", [userID])["rank"]


def getUserSilenceEnd(userID):
	"""
	Get userID's **ABSOLUTE** silence end UNIX time
	Remember to subtract time.time() to get the actual silence time

	userID -- userID
	return -- UNIX time
	"""

	return glob.db.fetch("SELECT silence_end FROM users WHERE osu_id = ?", [userID])["silence_end"]


def getUserRankedScore(userID, gameMode):
	"""
	Get userID's ranked score relative to gameMode

	userID -- userID
	gameMode -- int value, see gameModes
	return -- ranked score
	"""

	modeForDB = gameModes.getGameModeForDB(gameMode)
	return glob.db.fetch("SELECT ranked_score_"+modeForDB+" FROM users_stats WHERE osu_id = ?", [userID])["ranked_score_"+modeForDB]


def getUserTotalScore(userID, gameMode):
	"""
	Get userID's total score relative to gameMode

	userID -- userID
	gameMode -- int value, see gameModes
	return -- total score
	"""

	modeForDB = gameModes.getGameModeForDB(gameMode)
	return glob.db.fetch("SELECT total_score_"+modeForDB+" FROM users_stats WHERE osu_id = ?", [userID])["total_score_"+modeForDB]


def getUserAccuracy(userID, gameMode):
	"""
	Get userID's average accuracy relative to gameMode

	userID -- userID
	gameMode -- int value, see gameModes
	return -- accuracy
	"""

	modeForDB = gameModes.getGameModeForDB(gameMode)
	return glob.db.fetch("SELECT avg_accuracy_"+modeForDB+" FROM users_stats WHERE osu_id = ?", [userID])["avg_accuracy_"+modeForDB]


def getUserGameRank(userID, gameMode):
	"""
	Get userID's **in-game rank** (eg: #1337) relative to gameMode

	userID -- userID
	gameMode -- int value, see gameModes
	return -- game rank
	"""

	modeForDB = gameModes.getGameModeForDB(gameMode)
	result = glob.db.fetch("SELECT position FROM leaderboard_"+modeForDB+" WHERE user = ?", [userID])
	if (result == None):
		return 0
	else:
		return result["position"]


def getUserPlaycount(userID, gameMode):
	"""
	Get userID's playcount relative to gameMode

	userID -- userID
	gameMode -- int value, see gameModes
	return -- playcount
	"""

	modeForDB = gameModes.getGameModeForDB(gameMode)
	return glob.db.fetch("SELECT playcount_"+modeForDB+" FROM users_stats WHERE osu_id = ?", [userID])["playcount_"+modeForDB]


# TODO: Remove user user user user meme from function names
def getUserUsername(userID):
	"""
	Get userID's username

	userID -- userID
	return -- username
	"""

	return glob.db.fetch("SELECT username FROM users WHERE osu_id = ?", [userID])["username"]


def getFriendList(userID):
	"""
	Get userID's friendlist

	userID -- userID
	return -- list with friends userIDs. [0] if no friends.
	"""

	# Get friends from db
	friends = glob.db.fetch("SELECT friends FROM users WHERE osu_id = ?", [userID])["friends"]

	if (friends == None or friends == ""):
		# We have no friends, return 0 list
		return [0]
	else:
		# If we have some friends, split to get their IDs
		friends = friends.split(",")

		# Cast strings to ints
		friends = [int(i) for i in friends]

		# Return friend IDs
		return friends


def addFriend(userID, friendID):
	"""
	Add friendID to userID's friend list

	userID -- user
	friendID -- new friend
	"""

	# Get current friend list
	friends = glob.db.fetch("SELECT friends FROM users WHERE osu_id = ?", [userID])["friends"]

	# Values from db are strings, go convert friendID to string
	friendID = str(friendID)

	# Split in array, append new friend (if not already in friend list) and join array to string again
	friends = friends.split(",")
	if (friendID in friends):
		return
	friends.append(friendID)
	friends = str.join(",", friends)

	# Set new value
	glob.db.execute("UPDATE users SET friends = ? WHERE osu_id = ?", [friends, userID])


def removeFriend(userID, friendID):
	"""
	Remove friendID from userID's friend list

	userID -- user
	friendID -- old friend
	"""

	# Get current friend list
	friends = glob.db.fetch("SELECT friends FROM users WHERE osu_id = ?", [userID])["friends"]

	# Values from db are strings, go convert friendID to string
	friendID = str(friendID)

	# Split in array, remove friend (if it is in friend list) and join array to string again
	friends = friends.split(",")
	if (friendID not in friends):
		return
	friends.remove(friendID)
	friends = str.join(",", friends)

	# Set new value
	glob.db.execute("UPDATE users SET friends = ? WHERE osu_id = ?", [friends, userID])


def getCountry(userID):
	"""
	Get userID's country **(two letters)**.
	Use countryHelper.getCountryID with what that function returns
	to get osu! country ID relative to that user

	userID -- user
	return -- country code (two letters)
	"""

	return glob.db.fetch("SELECT country FROM users_stats WHERE osu_id = ?", [userID])["country"]
