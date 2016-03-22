from flask import Flask, send_file
import os
app = Flask(__name__)
app.config['SEND_FILE_MAX_AGE_DEFAULT'] = 1

@app.route("/<int:osuid>")
def serveAvatar(osuid):
	# Check if avatar exists
	if os.path.isfile("avatars/%d.png" % osuid):
		avatarid = osuid
	else:
		avatarid = 0

	# Serve actual avatar or default one
	return send_file("avatars/%d.png" % avatarid)

# Run the server
app.run(host="0.0.0.0", port=5000)