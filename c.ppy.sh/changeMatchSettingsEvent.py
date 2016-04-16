import glob
import clientPackets
import matchModModes
import consoleHelper
import bcolors
import random
import matchTeamTypes
import matchTeams
import slotStatuses

def handle(userToken, packetData):
	# Read new settings
	packetData = clientPackets.changeMatchSettings(packetData)

	# Get match ID
	matchID = userToken.matchID

	# Make sure the match exists
	if matchID not in glob.matches.matches:
		return

	# Get match object
	match = glob.matches.matches[matchID]

	# Some dank memes easter egg
	memeTitles = [
		"RWC 2020",
		"Fokabot is a duck",
		"Dank memes",
		"1337ms Ping",
		"Iscriviti a Xenotoze",
		"...e i marò?",
		"Superman dies",
		"The brace is on fire",
		"print_foot()",
		"#FREEZEBARKEZ",
		"Ripple devs are actually cats",
		"Thank Mr Shaural",
		"NEVER GIVE UP",
		"T I E D  W I T H  U N I T E D",
		"HIGHEST HDHR LOBBY OF ALL TIME",
		"This is gasoline and I set myself on fire",
		"Everyone is cheating apparently",
		"Kurwa mac",
		"TATOE",
		"This is not your drama landfill.",
		"I like cheese",
		"NYO IS NOT A CAT HE IS A DO(N)G",
		"Datingu startuato"
	]

	# Set match name
	match.matchName = packetData["matchName"] if packetData["matchName"] != "meme" else random.choice(memeTitles)

	# Update match settings
	match.inProgress = packetData["inProgress"]
	match.matchPassword = packetData["matchPassword"]
	match.beatmapName = packetData["beatmapName"]
	match.beatmapID = packetData["beatmapID"]
	match.hostUserID = packetData["hostUserID"]
	match.gameMode = packetData["gameMode"]

	oldBeatmapMD5 = match.beatmapMD5
	oldMods = match.mods

	match.mods = packetData["mods"]
	match.beatmapMD5 = packetData["beatmapMD5"]
	match.matchScoringType = packetData["scoringType"]
	match.matchTeamType = packetData["teamType"]
	match.matchModMode = packetData["freeMods"]

	# Reset ready if needed
	if oldMods != match.mods or oldBeatmapMD5 != match.beatmapMD5:
		for i in range(0,16):
			if match.slots[i]["status"] == slotStatuses.ready:
				match.slots[i]["status"] = slotStatuses.notReady

	# Reset mods if needed
	if match.matchModMode == matchModModes.normal:
		# Reset slot mods if not freeMods
		for i in range(0,16):
			match.slots[i]["mods"] = 0
	else:
		# Reset match mods if freemod
		match.mods = 0

	# Set/reset teams
	if match.matchTeamType == matchTeamTypes.teamVs or match.matchTeamType == matchTeamTypes.tagTeamVs:
		# Set teams
		c=0
		for i in range(0,16):
			if match.slots[i]["team"] == matchTeams.noTeam:
				match.slots[i]["team"] = matchTeams.red if c % 2 == 0 else matchTeams.blue
				c+=1
	else:
		# Reset teams
		for i in range(0,16):
			match.slots[i]["team"] = matchTeams.noTeam

	# Force no freemods if tag coop
	if match.matchTeamType == matchTeamTypes.tagCoop or match.matchTeamType == matchTeamTypes.tagTeamVs:
		match.matchModMode = matchModModes.normal

	# Send updated settings
	match.sendUpdate()

	# Console output
	consoleHelper.printColored("> MPROOM{}: Updated room settings".format(match.matchID), bcolors.BLUE)
	#consoleHelper.printColored("> MPROOM{}: DEBUG: Host is {}".format(match.matchID, match.hostUserID), bcolors.PINK)
