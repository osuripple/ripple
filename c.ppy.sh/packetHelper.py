import struct
import packets
import bcolors
import sys
import dataTypes

def packData(__data, __dataType):
	"""Packs data according to dataType

	data -- bytes to pack
	dataType -- data type. See dataTypes.py

	return -- packed bytes"""

	data = bytes()	# data to return
	pack = True		# if True, use pack. False only with strings

	# Get right pack Type
	if (__dataType == dataTypes.string):
		# String, do not use pack, do manually
		pack = False
		data += b"\x0B"
		data += struct.pack("B", len(__data))	# TODO: uleb128 thing
		data += str.encode(__data, "UTF-8")
	elif (__dataType == dataTypes.uInt16):
		packType = "H"
	elif (__dataType == dataTypes.sInt16):
		packType = "h"
	elif (__dataType == dataTypes.uInt32):
		packType = "L"
	elif (__dataType == dataTypes.sInt32):
		packType = "l"
	elif (__dataType == dataTypes.uInt64):
		packType = "Q"
	elif (__dataType == dataTypes.uInt64):
		packType = "q"
	elif (__dataType == dataTypes.string):
		packType = "s"
	elif (__dataType == dataTypes.ffloat):
		packType = "f"
	else:
		packType = "B"

	if (pack == True):
		data += struct.pack(packType, __data)

	return data


def buildPacket(__packet, __packetData = []):
	"""Build a packet

	packet -- packet id (int)
	packetData -- array [[data, dataType], [data, dataType], ...]

	return -- packet bytes"""

	try:
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
		packetBytes += struct.pack("h", __packet)		# packet id (int16)
		packetBytes += bytes(b"\x00")					# unused byte
		packetBytes += struct.pack("l", packetLength)	# packet lenght (iint32)
		packetBytes += packetData						# packet data
		#print(str(packetBytes))
		return packetBytes
	except:
		print(bcolors.RED+"[!] Error while building packet!"+"\r\n"+str(sys.exc_info()[1])+bcolors.ENDC)
