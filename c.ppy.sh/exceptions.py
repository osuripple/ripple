"""Bancho exceptions"""
# TODO: Prints in exceptions
class loginFailedException(Exception):
	pass

class loginBannedException(Exception):
	pass

class tokenNotFoundException(Exception):
	pass

class channelNoPermissionsException(Exception):
	pass

class channelUnknownException(Exception):
	pass

class channelModeratedException(Exception):
	pass

class noAdminException(Exception):
	pass

class commandSyntaxException(Exception):
	pass

class banchoConfigErrorException(Exception):
	pass

class banchoMaintenanceException(Exception):
	pass

class moderatedPMException(Exception):
	pass

class userNotFoundException(Exception):
	pass

class alreadyConnectedException(Exception):
	pass

class stopSpectating(Exception):
	pass

class matchWrongPasswordException(Exception):
	pass

class matchNotFoundException(Exception):
	pass

class matchJoinErrorException(Exception):
	pass

class matchCreateError(Exception):
	pass

class banchoRestartingException(Exception):
	pass
