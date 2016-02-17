import crypt
import base64

def checkPassword(__password, __salt, __rightPassword):
	if (__rightPassword == crypt.crypt(__password, "$2y$"+str(base64.b64decode(__salt)))):
		return True
	else:
		return False
