from lets import glob
from helpers import scoreHelper
from helpers import consoleHelper
from constants import bcolors
from helpers import passwordHelper
import time

def getID(username):
	"""
	Get username's user ID

	username -- user
	return -- user id or 0
	"""

	# Get user ID from db
	userID = glob.db.fetch("SELECT id FROM users WHERE username = ?", [username])

	# Make sure the query returned something
	if userID == None:
		return 0

	# Return user ID
	return userID["id"]

def getUsername(userID):
	"""
	Get userID's username

	userID -- userID
	return -- username or None
	"""
	result = glob.db.fetch("SELECT username FROM users WHERE id = ?", [userID])
	if result == None:
		return None
	return result["username"]


def exists(userID):
	"""
	Check if given userID exists

	userID -- user id to check
	"""
	return True if glob.db.fetch("SELECT id FROM users WHERE id = ?", [userID]) != None else False


def checkLogin(userID, password):
	"""
	Check userID's login with specified password

	userID -- user id
	password -- plain md5 password
	return -- True or False
	"""

	# Get password data
	passwordData = glob.db.fetch("SELECT password_md5, salt, password_version FROM users WHERE id = ?", [userID])

	# Make sure the query returned something
	if passwordData == None:
		return False

	# Return valid/invalid based on the password version.
	if passwordData["password_version"] == 2:
		return passwordHelper.checkNewPassword(password, passwordData["password_md5"])
	if passwordData["password_version"] == 1:
		ok = passwordHelper.checkOldPassword(password, passwordData["salt"], passwordData["password_md5"])
		if not ok: return False
		newpass = passwordHelper.genBcrypt(password)
		glob.db.execute("UPDATE users SET password_md5=?, salt='', password_version='2' WHERE id = ?", [newpass, userID])


def getRequiredScoreForLevel(level):
	"""
	Return score required to reach a level

	level -- level to reach
	return -- required score
	"""
	if level <= 100:
		if level >= 2:
			return 5000 / 3 * (4 * (level ** 3) - 3 * (level ** 2) - level) + 1.25 * (1.8 ** (level - 60))
		elif level <= 0 or level == 1:
			return 1	# Should be 0, but we get division by 0 below so set to 1
	elif level >= 101:
		return 26931190829 + 100000000000 * (level - 100)


def getLevel(totalScore):
	"""
	Return level from totalScore

	totalScore -- total score
	return -- level
	"""
	level = 1
	while True:
		# if the level is > 8000, it's probably an endless loop. terminate it.
		if level > 8000:
			return level

		# Calculate required score
		reqScore = getRequiredScoreForLevel(level)

		# Check if this is our level
		if totalScore <= reqScore:
			# Our level, return it and break
			return level
		else:
			# Not our level, calculate score for next level
			level+=1

def updateLevel(userID, gameMode):
	"""
	Update level in DB for userID relative to gameMode
	"""
	if not userExists(userID):
		return

	mode = scoreHelper.readableGameMode(gameMode)
	totalScore = glob.db.fetch("SELECT total_score_{m} FROM users_stats WHERE id = ?".format(m = mode), [userID])
	level = getLevel(totalScore["total_score_{m}".format(m = mode)])
	glob.db.execute("UPDATE users_stats SET level_{m} = ? WHERE id = ?".format(m = mode), [level, userID])


def calculateAccuracy(userID, gameMode):
	"""
	Calculate accuracy value for userID relative to gameMode

	userID --
	gameMode -- gameMode number
	return -- new accuracy
	"""
	# Make sure the score is from the same user
	username = getUsername(userID)
	if username == None:
		return 0

	# Get best accuracy scores
	bestAccScores = glob.db.fetchAll("SELECT accuracy FROM scores WHERE username = ? AND play_mode = ? AND completed = '3' ORDER BY accuracy DESC LIMIT 100", [username, gameMode])

	v = 0
	if bestAccScores != None:
		# Calculate weighted accuracy
		totalAcc = 0
		divideTotal = 0
		k = 0
		for i in bestAccScores:
			add = int( (0.95 ** k) * 100)
			totalAcc += i["accuracy"] * add
			divideTotal += add
			k += 1
			# echo "$add - $totalacc - $divideTotal\n"
		if divideTotal != 0:
			v = totalAcc / divideTotal
		else:
			v = 0
	return v


def updateAccuracy(userID, gameMode):
	"""
	Update accuracy value for userID relative to gameMode in DB

	userID --
	gameMode -- gameMode number
	"""

	username = getUsername(userID)
	if username == None:
		return
	newAcc = calculateAccuracy(userID, gameMode)
	mode = scoreHelper.readableGameMode(gameMode)
	glob.db.execute("UPDATE users_stats SET avg_accuracy_{m} = ? WHERE username = ?".format(m = mode), [newAcc, username])




def updateStats(userID, __score):
	"""
	Update stats (playcount, total score, ranked score, level bla bla)
	with data relative to a score object

	userID --
	__score -- score object
	"""

	# Make sure the user exists
	if not userExists(userID):
		consoleHelper.printColored("[!] User {} doesn't exist.".format(userID), bcolors.RED)
		return

	# Get gamemode for db
	mode = scoreHelper.readableGameMode(__score.gameMode)

	# Update total score and playcount
	glob.db.execute("UPDATE users_stats SET total_score_{m}=total_score_{m}+?, playcount_{m}=playcount_{m}+1 WHERE id = ?".format(m=mode), [__score.score, userID])

	# Calculate new level and update it
	updateLevel(userID, __score.gameMode)

	# Update level, accuracy and ranked score only if we have passed the song
	if __score.passed == True:
		# Update ranked score
		glob.db.execute("UPDATE users_stats SET ranked_score_{m}=ranked_score_{m}+? WHERE id = ?".format(m=mode), [__score.rankedScoreIncrease, userID])

		# Update accuracy
		updateAccuracy(userID, __score.gameMode)

		# Update pp
		updatePP(userID, __score.pp, __score.gameMode)

		# TODO: Update leaderboard

def updatePP(userID, pp, gameMode):
	"""
	Update userID's pp with new value

	userID -- userID
	pp -- pp to add
	gameMode -- gameMode number
	"""
	# Make sure the user exists
	if not userExists(userID):
		consoleHelper.printColored("[!] User {} doesn't exist.".format(userID), bcolors.RED)
		return

	# TODO: weighted pp
	mode = scoreHelper.readableGameMode(gameMode)
	glob.db.execute("UPDATE users_stats SET pp_{m}=pp_{m}+? WHERE id = ?".format(m = mode), [pp, userID])

def getAllowed(userID):
	"""
	Get allowed status for userID

	db -- database connection
	userID -- user ID
	return -- allowed int
	"""

	result = glob.db.fetch("SELECT allowed FROM users WHERE id = ?", [userID])
	if result != None:
		return result["allowed"]
	else:
		return None

def updateLatestActivity(userID):
	"""
	Update userID's latest activity to current UNIX time

	userID --
	"""
	glob.db.execute("UPDATE users SET latest_activity = ? WHERE id = ?", [int(time.time()), userID])
