"""
WIP feature that will come in the future.
Don't import
"""
import flask
import glob
import exceptions

@app.route("/api/online-users-count")
def APIonlineUsersCount():
	return flask.jsonify({"count" : len(glob.tokens.tokens)-1})

@app.route("/api/user-info")
def APIonlineUsers():
	resp = {}

	try:
		u = flask.request.args.get('u')

		# Username/userID
		if u.isdigit():
			u = int(u)
		else:
			u = userHelper.getID(u)
			if u == None:
				raise exceptions.userNotFoundException

		# Make sure this user is online
		userToken = glob.tokens.getTokenFromUserID(u)
		if userToken == None:
			raise exceptions.tokenNotFoundException

		# Build response dictionary
		resp["response"] = "1"
		resp[userToken.username] = {
			"userID" : 		userToken.userID,
			"actionID" : 	userToken.actionID,
			"actionText" : 	userToken.actionText,
			"actionMd5" : 	userToken.actionMd5,
			"actionMods": 	userToken.actionMods,
			"gameMode": 	userToken.gameMode,
			"country":		countryHelper.getCountryLetters(userToken.country),
			"position":		userToken.location,
			"spectating":	userToken.spectating,
			"spectators":	userToken.spectators
		}
	except exceptions.userNotFoundException:
		resp["response"] = "-1"
	except exceptions.tokenNotFoundException:
		resp["response"] = "-2"
	finally:
		return flask.jsonify(resp)
