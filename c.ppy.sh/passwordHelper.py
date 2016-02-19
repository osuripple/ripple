import crypt
import base64

def checkPassword(__password, __salt, __rightPassword):
	"""Check if password+salt corresponds to rightPassword

	__password -- input password
	__salt -- __password's salt
	__rightPassword -- right password
	__params -- array with params. Optional

	return -- bool"""
	if (__rightPassword == crypt.crypt(__password, "$2y$"+str(base64.b64decode(__salt)))):
		return True
	else:
		return False
