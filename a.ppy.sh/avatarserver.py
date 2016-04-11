from flask import Flask, send_file, jsonify
import os
app = Flask(__name__)
app.config['SEND_FILE_MAX_AGE_DEFAULT'] = 1

avatar_dir = "avatars" # no slash

# create avatars directory if it does not exist
if not os.path.exists(avatar_dir):
	os.makedirs(avatar_dir)


@app.route("/status")
def serverStatus():
	return jsonify({
		"response" : 200,
		"status" : 1
	})

@app.route("/<int:uid>")
def serveAvatar(uid):
	# Check if avatar exists
	if os.path.isfile("{}/{}.png".format(avatar_dir, uid)):
		avatarid = uid
	else:
		avatarid = 0

	# Serve actual avatar or default one
	return send_file("{}/{}.png".format(avatar_dir, avatarid))

# Run the server
app.run(host="0.0.0.0", port=5000)
