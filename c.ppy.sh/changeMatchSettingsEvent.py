import glob
import clientPackets
import matchModModes
import consoleHelper
import bcolors

def handle(userToken, packetData):
	# Read new settings
	packetData = clientPackets.changeMatchSettings(packetData)

	# Get match ID
	matchID = userToken.matchID

	# Make sure the match exists
	if (matchID not in glob.matches.matches):
		return

	# Get match object
	match = glob.matches.matches[matchID]

	# Update match settings
	match.inProgress = packetData["inProgress"]
	match.mods = packetData["mods"]
	match.matchName = packetData["matchName"]
	match.matchPassword = packetData["matchPassword"]
	match.beatmapName = packetData["beatmapName"]
	match.beatmapID = packetData["beatmapID"]
	match.beatmapMD5 = packetData["beatmapMD5"]
	match.hostUserID = packetData["hostUserID"]
	match.gameMode = int(packetData["gameMode"])
	match.scoringType = int(packetData["scoringType"])
	match.teamType = int(packetData["teamType"])
	match.matchModMode = int(packetData["freeMods"])

	if (match.matchModMode == matchModModes.normal):
		# Reset slot mods if not freeMods
		for i in range(0,16):
			match.slots[i]["mods"] = 0
	else:
		# Reset match mods if freemod
		match.mods = 0

	# Send updated settings
	glob.matches.matches[matchID].sendUpdate()

	# Meme
	print(str(packetData))

	# Console output
	consoleHelper.printColored("> MPROOM{}: Updated room settings\nGame mode: {}\nScoring type: {}\nTeam type: {}\nMod mode: {}".format(matchID, match.gameMode, match.matchScoringType, match.matchTeamType, match.matchModMode), bcolors.BLUE)
