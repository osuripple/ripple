from constants import exceptions

def checkArguments(arguments, requiredArguments):
	"""
	Check that every requiredArguments elements are in arguments

	arguments -- full argument list, from tornado
	requiredArguments -- required arguments list es: ["u", "ha"]
	handler -- handler string name to print in exception. Optional
	return -- True if all arguments are passed, none if not
	"""
	for i in requiredArguments:
		if i not in arguments:
			return False
	return True
