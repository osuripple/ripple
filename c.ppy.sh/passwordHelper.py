import crypt
import base64

def checkPassword(password, salt, rightPassword):
	"""
	Check if password+salt corresponds to rightPassword

	password -- input password
	salt -- password's salt
	rightPassword -- right password
	return -- bool
	"""

	return (rightPassword == crypt.crypt(password, "$2y$"+str(base64.b64decode(salt))))
