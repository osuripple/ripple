import flask
from flask import Flask, request
import gzip

# TODO: Remove class
class response:
	token = None
	resp = None

	def __init__(self, __token, __data = None):
		self.resp = flask.Response(gzip.compress(__data, 6))
		self.token = __token
		self.resp.headers['cho-token'] = self.token
		self.resp.headers['cho-protocol'] = '19'
		self.resp.headers['Keep-Alive'] = 'timeout=5, max=100'
		self.resp.headers['Connection'] = 'Keep-Alive'
		self.resp.headers['Content-Type'] = 'text/html; charset=UTF-8'
		self.resp.headers['Vary'] = 'Accept-Encoding'
		self.resp.headers['Content-Encoding'] = 'gzip'

	def getResponse(self):
		return self.resp
