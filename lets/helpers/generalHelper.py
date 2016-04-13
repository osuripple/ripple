import string
import random

def randomString(length = 8):
	return ''.join(random.choice(string.ascii_uppercase + string.digits) for _ in range(length))
