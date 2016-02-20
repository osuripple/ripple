std 	= 0
taiko 	= 1
ctb 	= 2
mania 	= 3

def getGameModeForDB(gameMode):
	if (gameMode == std):
		return "std"
	elif (gameMode == taiko):
		return "taiko"
	elif (gameMode == ctb):
		return "ctb"
	else:
		return "mania"
