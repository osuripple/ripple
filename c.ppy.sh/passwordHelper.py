import crypt
import base64
import bcrypt

def checkOldPassword(password, salt, rightPassword):
	"""
	Check if password+salt corresponds to rightPassword

	password -- input password
	salt -- password's salt
	rightPassword -- right password
	return -- bool
	"""

	return (rightPassword == crypt.crypt(password, "$2y$"+str(base64.b64decode(salt))))

def checkNewPassword(password, dbPassword):
	"""
	Check if a password (version 2) is right.
	
	password -- input password
	dbPassword -- the password in the database
	return -- bool
	"""
	password = password.encode("utf8")
	dbPassword = dbPassword.encode("utf8")	
	return bcrypt.hashpw(password, dbPassword) == dbPassword

def genBcrypt(password):
	"""
	Bcrypts a password.
	
	password -- the password to hash.
	return -- bytestring
	"""
	return bcrypt.hashpw(password.encode("utf8"), bcrypt.gensalt(10, b'2a'))
