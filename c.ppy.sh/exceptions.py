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
