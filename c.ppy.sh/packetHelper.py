import struct
import dataTypes

def uleb128Encode(num):
	"""
	Encode int -> uleb128

	num -- int to encode
	return -- bytearray with encoded number
	"""

	arr = bytearray()
	length = 0

	if num == 0:
		return bytearray(b"\x00")

	while num > 0:
		arr.append(num & 127)
		num = num >> 7
		if num != 0:
			arr[length] = arr[length] | 128
		length+=1

	return arr


def uleb128Decode(num):
	"""
	Decode uleb128 -> int

	num -- encoded uleb128
	return -- list. [total, length]
	"""

	shift = 0

	arr = [0,0]	#total, length

	while True:
		b = num[arr[1]]
		arr[1]+=1
		arr[0] = arr[0] | (int(b & 127) << shift)
		if b & 128 == 0:
			break
		shift += 7

	return arr


def unpackData(__data, __dataType):
	"""
	Unpacks data according to dataType

	__data -- bytes array to unpack
	__dataType -- data type. See dataTypes.py

	return -- unpacked bytes
	"""

	# Get right pack Type
	if __dataType == dataTypes.uInt16:
		unpackType = "<H"
	elif __dataType == dataTypes.sInt16:
		unpackType = "<h"
	elif __dataType == dataTypes.uInt32:
		unpackType = "<L"
	elif __dataType == dataTypes.sInt32:
		unpackType = "<l"
	elif __dataType == dataTypes.uInt64:
		unpackType = "<Q"
	elif __dataType == dataTypes.sInt64:
		unpackType = "<q"
	elif __dataType == dataTypes.string:
		unpackType = "<s"
	elif __dataType == dataTypes.ffloat:
		unpackType = "<f"
	else:
		unpackType = "<B"

	# Unpack
	return struct.unpack(unpackType, bytes(__data))[0]


def packData(__data, __dataType):
	"""
	Packs data according to dataType

	data -- bytes to pack
	dataType -- data type. See dataTypes.py

	return -- packed bytes
	"""

	data = bytes()	# data to return
	pack = True		# if True, use pack. False only with strings

	# Get right pack Type
	if __dataType == dataTypes.bbytes:
		# Bytes, do not use pack, do manually
		pack = False
		data = __data
	elif __dataType == dataTypes.string:
		# String, do not use pack, do manually
		pack = False
		if len(__data) == 0:
			# Empty string
			data += b"\x00"
		else:
			# Non empty string
			data += b"\x0B"
			data += uleb128Encode(len(__data))
			data += str.encode(__data, "latin_1")
	elif __dataType == dataTypes.uInt16:
		packType = "<H"
	elif __dataType == dataTypes.sInt16:
		packType = "<h"
	elif __dataType == dataTypes.uInt32:
		packType = "<L"
	elif __dataType == dataTypes.sInt32:
		packType = "<l"
	elif __dataType == dataTypes.uInt64:
		packType = "<Q"
	elif __dataType == dataTypes.sInt64:
		packType = "<q"
	elif __dataType == dataTypes.string:
		packType = "<s"
	elif __dataType == dataTypes.ffloat:
		packType = "<f"
	else:
		packType = "<B"

	# Pack if needed
	if pack == True:
		data += struct.pack(packType, __data)

	return data

# TODO: Wat dangerous
def buildPacket(__packet, __packetData = []):
	"""
	Build a packet

	packet -- packet id (int)
	packetData -- list [[data, dataType], [data, dataType], ...]

	return -- packet bytes
	"""

	# Set some variables
	packetData = bytes()
	packetLength = 0
	packetBytes = bytes()

	# Pack packet data
	for i in __packetData:
		packetData += packData(i[0], i[1])

	# Set packet length
	packetLength = len(packetData)

	# Return packet as bytes
	packetBytes += struct.pack("<h", __packet)		# packet id (int16)
	packetBytes += bytes(b"\x00")					# unused byte
	packetBytes += struct.pack("<l", packetLength)	# packet lenght (iint32)
	packetBytes += packetData						# packet data
	return packetBytes


def readPacketID(__stream):
	"""
	Read packetID from __stream (0-1 bytes)

	__stream -- data stream
	return -- packet ID (int)
	"""

	return unpackData(__stream[0:2], dataTypes.uInt16)


def readPacketLength(__stream):
	"""
	Read packet length from __stream (3-4-5-6 bytes)

	__stream -- data stream
	return -- packet length (int)
	"""

	return unpackData(__stream[3:7], dataTypes.uInt32)


def readPacketData(__stream, __structure = [], __hasFirstBytes = True):
	"""
	Read packet data from __stream according to __structure

	__stream -- data stream
	__structure -- [[name, dataType], [name, dataType], ...]
	__hasFirstBytes -- 	if True, __stream has packetID and length bytes.
						if False, __stream has only packetData.
						Optional. Default: True
	return -- dictionary. key: name, value: read data
	"""

	# Read packet ID (first 2 bytes)
	data = {}

	# Skip packet ID and packet length if needed
	if __hasFirstBytes == True:
		end = 7
		start = 7
	else:
		end = 0
		start = 0

	# Read packet
	for i in __structure:
		start = end
		unpack = True
		if i[1] == dataTypes.string:
			# String, don't unpack
			unpack = False

			# Check empty string
			if __stream[start] == 0:
				# Empty string
				data[i[0]] = ""
				end = start+1
			else:
				# Non empty string
				# Read length and calculate end
				length = uleb128Decode(__stream[start+1:])
				end = start+length[0]+length[1]+1

				# Read bytes
				data[i[0]] = ''.join(chr(j) for j in __stream[start+1+length[1]:end])
		elif i[1] == dataTypes.byte:
			end = start+1
		elif i[1] == dataTypes.uInt16 or i[1] == dataTypes.sInt16:
			end = start+2
		elif i[1] == dataTypes.uInt32 or i[1] == dataTypes.sInt32:
			end = start+4
		elif i[1] == dataTypes.uInt64 or i[1] == dataTypes.sInt64:
			end = start+8

		# Unpack if needed
		if unpack == True:
			data[i[0]] = unpackData(__stream[start:end], i[1])

	return data
