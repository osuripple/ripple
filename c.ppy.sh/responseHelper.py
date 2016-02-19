import flask
import gzip

def generateResponse(token, data = None):
	resp = flask.Response(gzip.compress(data, 6))
	resp.headers['cho-token'] = token
	resp.headers['cho-protocol'] = '19'
	resp.headers['Keep-Alive'] = 'timeout=5, max=100'
	resp.headers['Connection'] = 'Keep-Alive'
	resp.headers['Content-Type'] = 'text/html; charset=UTF-8'
	resp.headers['Vary'] = 'Accept-Encoding'
	resp.headers['Content-Encoding'] = 'gzip'
	return resp
