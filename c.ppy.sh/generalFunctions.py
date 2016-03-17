"""Some functions that don't fit in any other file"""

def stringToBool(s):
	"""
	Convert a string (True/true/1) to bool

	s -- string/int value
	return -- True/False
	"""

	return (s == "True" or s== "true" or s == "1" or s == 1)


def hexString(s):
	"""
	Output s' bytes in HEX

	s -- string
	return -- string with hex value
	"""

	return ":".join("{:02x}".format(ord(c)) for c in s)
