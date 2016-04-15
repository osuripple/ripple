from constants import mods

def isRankable(m):
	"""
	Return True if there are no unranked mods, otherwise False

	m -- mods to check
	return -- True if rankable, False if not rankable
	"""
	return not ((m & mods.RELAX > 0) or (m & mods.RELAX2 > 0) or (m & mods.AUTOPLAY > 0))

def readableGameMode(gameMode):
	"""
	Convert numeric gameMode to a readable format. Can be used for db too

	gameMode -- gameMode number
	"""
	if gameMode == 0:
		return "std"
	elif gameMode == 1:
		return "taiko"
	elif gameMode == 2:
		return "ctb"
	else:
		return "mania"
