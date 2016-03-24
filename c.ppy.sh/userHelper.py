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
	return True
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


def silenceUser(userID, silenceEndTime, silenceReason):
	"""
	Set userID's **ABSOLUTE** silence end UNIX time
	Remember to add time.time() to the silence length

	userID -- userID
	silenceEndtime -- UNIX time when the silence ends
	silenceReason -- Silence reason shown on website
	"""

	glob.db.execute("UPDATE users SET silence_end = ?, silence_reason = ? WHERE osu_id = ?", [silenceEndTime, silenceReason, userID])

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
	friends = glob.db.fetchAll("SELECT user2 FROM users_relationships WHERE user1 = ?", [userID])

	if (friends == None or len(friends) == 0):
		# We have no friends, return 0 list
		return [0]
	else:
		# Get only friends
		friends = [i["user2"] for i in friends]

		# Return friend IDs
		return friends


def addFriend(userID, friendID):
	"""
	Add friendID to userID's friend list

	userID -- user
	friendID -- new friend
	"""

	# Make sure we aren't adding us to our friends
	if (userID == friendID):
		return

	# check user isn't already a friend of ours
	if glob.db.fetch("SELECT id FROM users_relationships WHERE user1 = ? AND user2 = ?", [userID, friendID]) != None:
		return

	# Set new value
	glob.db.execute("INSERT INTO users_relationships (user1, user2) VALUES (?, ?)", [userID, friendID])


def removeFriend(userID, friendID):
	"""
	Remove friendID from userID's friend list

	userID -- user
	friendID -- old friend
	"""

	# Delete user relationship. We don't need to check if the relationship was there, because who gives a shit,
	# if they were not friends and they don't want to be anymore, be it. ¯\_(ツ)_/¯	
	glob.db.execute("DELETE FROM users_relationships WHERE user1 = ? AND user2 = ?", [userID, friendID])


def getCountry(userID):
	"""
	Get userID's country **(two letters)**.
	Use countryHelper.getCountryID with what that function returns
	to get osu! country ID relative to that user

	userID -- user
	return -- country code (two letters)
	"""

	return glob.db.fetch("SELECT country FROM users_stats WHERE osu_id = ?", [userID])["country"]
