from flask import Flask, send_file
import os
app = Flask(__name__)
app.config['SEND_FILE_MAX_AGE_DEFAULT'] = 1

avatar_dir = "avatars" # no slash

# create avatars directory if it does not exist
if not os.path.exists(avatar_dir):
	os.makedirs(avatar_dir)
    
@app.route("/<int:id>")
def serveAvatar(id):
	# Check if avatar exists
	if os.path.isfile("{}/{}.png".format(avatar_dir, id)):
		avatarid = id
	else:
		avatarid = 0

	# Serve actual avatar or default one
	return send_file("{}/{}.png".format(avatar_dir, avatarid))

# Run the server
app.run(host="0.0.0.0", port=5000)
