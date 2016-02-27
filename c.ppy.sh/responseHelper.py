import flask
import gzip

def generateResponse(token, data = None):
	"""Return a flask response with required headers
	right token and gzip compressed data

	token -- user token
	data -- plain response body

	return -- flask response"""

	resp = flask.Response(gzip.compress(data, 6))
	resp.headers['cho-token'] = token
	resp.headers['cho-protocol'] = '19'
	resp.headers['Keep-Alive'] = 'timeout=5, max=100'
	resp.headers['Connection'] = 'Keep-Alive'
	resp.headers['Content-Type'] = 'text/html; charset=UTF-8'
	resp.headers['Vary'] = 'Accept-Encoding'
	resp.headers['Content-Encoding'] = 'gzip'
	return resp


def HTMLResponse():
	"""Return HTML bancho meme response"""
	
	html = 	"<html><head><title>MA MAURO ESISTE?</title><style type='text/css'>body{width:30%}</style></head><body><pre>"
	html += "           _                 __<br>"
	html += "          (_)              /  /<br>"
	html += "   ______ __ ____   ____  /  /____<br>"
	html += "  /  ___/  /  _  \\/  _  \\/  /  _  \\<br>"
	html += " /  /  /  /  /_) /  /_) /  /  ____/<br>"
	html += "/__/  /__/  .___/  .___/__/ \\_____/<br>"
	html += "        /  /   /  /<br>"
	html += "       /__/   /__/<br>"
	html += "<b>THERE'S A PYTHON ABROAD VERSION</b><br><br>"
	html += "<marquee style='white-space:pre;'><br>"
	html += "                          .. o  .<br>"
	html += "                         o.o o . o<br>"
	html += "                        oo...<br>"
	html += "                    __[]__<br>"
	html += "    phwr-->  _\\:D/_/o_o_o_|__     <span style=\"font-family: 'Comic Sans MS'; font-size: 8pt;\">u wot m8</span><br>"
	html += "             \\\"\"\"\"\"\"\"\"\"\"\"\"\"\"/<br>"
	html += "              \\ . ..  .. . /<br>"
	html += "^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^<br>"
	html += "</marquee><br><strike>reverse engineering a protocol impossible to reverse engineer since always</strike><br>we are actually reverse engineering bancho successfully. for the third time.</pre></body></html>"
