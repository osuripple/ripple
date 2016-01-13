<?php
	/*
	 * parseRequestHeaders
	 * This should fix nginx header memes
	 */
	function parseRequestHeaders() {
		$headers = array();
		foreach($_SERVER as $key => $value) {
			if (substr($key, 0, 5) <> 'HTTP_') {
				continue;
			}
			$header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
			$headers[$header] = $value;
		}
		return $headers;
	}

	/*
	 * outGz
	 * Outputs a gzip encoded string
	 *
	 * @param (string) ($str) Text to output
	 */
	function outGz($str) {
		echo(gzencode($str));
	}

	/*
	 * binStr
	 * Converts a string in a binary string
	 *
	 * @param (string) ($str) String
	 * @return (string) (0B+length+ASCII_STRING)
	 */
	function binStr($str) {
		$r = "";
		
		// Add 0B and length bytes
		$r .= "\x0B".pack("c", strlen($str));
		
		// Add Hex ASCII codes
		$r .= $str;
		
		// Return result
		return $r;
	}

	/*
	 * sendMessage
	 * Send a message to chat
	 *
	 * @param (string) ($from) From username
	 * @param (string) ($to) To username or channel
	 * @param (string) ($msg) Actual message
	 * @param (bool) ($priv) Set true if private message
	 * @return (string)
	 */
	function sendMessage($from, $to, $msg, $priv)
	{
		if ($priv) $type = 3; else $type = 2;
		$r = "";
		$r .= "\x07\x00\x00";
		$r .= pack("L", strlen("PRIVMSG ".$from." ".$msg." ".$to));	// i dont even know if this is right
		$r .= binStr($from);
		$r .= binStr($msg);
		$r .= binStr($to);
		$r .= pack("L", $type);
		return $r;
	}

	/*
	 * sendNotification
	 * Send a notification to client
	 * Is bugged as fuck with loooooong messages
	 * Use \\n for new line
	 *
	 * @param (string) ($msg) Notification message
	 * @return (string)
	 */
	function sendNotification($msg)
	{
		$r = "";
		$r .= "\x18\x00\x00";
		$r .= pack("L", strlen($msg)+2);
		$r .= binStr($msg);
		return $r;
	}
	
	/*
	 * banchoWeb
	 * Prints the bancho web meme
	 */
	function banchoWeb()
	{
		echo('<pre>
           _                 __
          (_)              /  /
   ______ __ ____   ____  /  /____
  /  ___/  /  _  \/  _  \/  /  _  \
 /  /  /  /  /_) /  /_) /  /  ____/   
/__/  /__/  .___/  .___/__/ \_____/
        /  /   /  /
       /__/   /__/
ripple 1.5 <u>bancho edition</u>
ripple 1.5 <u>on ripwot server</u>
ripple 1.5 <u>with less memes</u>
ripple 1.5 <u>with more features</u>
ripple 1.5 <u>free and open source</u>
ripple 1.5 <u>duck a fonkey</u>
ripple 1.5 <u><strike>(c)</strike> kwisk && phwr</u>
<marquee style="white-space:pre;">
                          .. o  .
                         o.o o . o
                        oo...
                    __[]__
    phwr-->  _\:D/_/o_o_o_|__     <span style="font-family: \'Comic Sans MS\'; font-size: 8pt;">u wot m8</span>
             \""""""""""""""/
              \ . ..  .. . /
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
</marquee>
<strike>reverse engineering a protocol impossible to reverse engineer since always</strike>
we are actually reverse engineering bancho successfully. kinda of.
</pre>');
	}
	
	/*
	 * banchoServer
	 * Main bancho """server""" function
	 */
	function banchoServer()
	{
		// Can't output before headers
		// We don't care about cho-token right now
		// because we handle only the login packets
		header("cho-token: puckfeppy");
		header("cho-protocol: 19");
		header("Keep-Alive: timeout=5, max=100");
		header("Connection: Keep-Alive");
		header("Content-Type: text/html; charset=UTF-8");
		header("Vary: Accept-Encoding");
		header("Content-Encoding: gzip");
		
		// Check maintenance
		if (checkBanchoMaintenance())
		{
			$output = "";
			$output .= sendNotification("Ripple's Bancho server is in manitenance mode.\\nCheck http://ripple.moe/ for more information.");
			$output .= "\x05\x00\x00\x04\x00\x00\x00\xFF\xFF\xFF\xFF";
			outGz($output);
			die();
		}

		// Check if this is the first packet
		if(!isset($_SERVER["HTTP_OSU_TOKEN"]))
		{
			// Check login
			try
			{
				// Get login data
				$data = file('php://input');

				// Get provided username and password.
				// We need to remove last character because it's new line
				// Fuck php
				$username = substr($data[0], 0, -1);
				$password = substr($data[1], 0, -1);

				// Check user/password
				if (!$GLOBALS["db"]->fetch("SELECT * FROM users WHERE username = ? AND password_md5 = ?", array($username, $password))) {
					throw new Exception("\xFF");
				}

				// Ban check
				if (current($GLOBALS["db"]->fetch("SELECT allowed FROM users WHERE username = ?", array($username))) == '0') {
					throw new Exception("\xFC");
				}
			}
			catch (Exception $e)
			{
				// Login failed
				// xFF: Login failed
				// xFE: Need update
				// xFC: Banned (CLIENT WILL BE LOCKED)
				// xFB: Error (use for maintenance and stuff)
				// xFA: Need supporter (wtf)
				outGz("\x05\x00\x00\x04\x00\x00\x00".$e->getMessage()."\xFF\xFF\xFF");
				die();
			}

			// Username, password and allowed are ok
			// Get user data and stats
			$userData = $GLOBALS["db"]->fetch("SELECT * FROM users WHERE username = ?", array($username));
			$userStats = $GLOBALS["db"]->fetch("SELECT * FROM users_stats WHERE username = ?", array($username));

			// Big meme here. Username is case-insensitive
			// but if we type it with wrong uppercase thing
			// there are memes in the userpanel. We don't
			// want it. Get the right username.
			$username = current($GLOBALS["db"]->fetch("SELECT username FROM users WHERE username = ?", array($username)));

			// Set variables
			// Supporter/GMT
			// x01: Normal (no supporter)
			// x02: GMT
			// x04: Supporter
			// x06: GMT + Supporter
			if (current($GLOBALS["db"]->fetch("SELECT value_int FROM bancho_settings WHERE name = 'free_direct'")) == 1)
				$defaultDirect = "\x04";
			else
				$defaultDirect = "\x01";
			$userSupporter = getUserRank($username) >= 3 ? "\x06" : $defaultDirect;
			$userID = $userData["osu_id"];
			$userCountry = 108;	// fixme plz

			// Unexpected copypasterino from Print.php
			// Get leaderboard with right total scores (to calculate rank)
			$leaderboard = $GLOBALS["db"]->fetchAll("SELECT osu_id FROM users_stats ORDER BY ranked_score_std DESC");

			// Get all allowed users on ripple
			$allowedUsers = getAllowedUsers("osu_id");

			// Calculate rank
			$userRank = 1;
			foreach ($leaderboard as $person) {
				if ($person["osu_id"] == $userID) // We found our user. We know our rank.
				break;
				if ($person["osu_id"] != 2 && $allowedUsers[$person["osu_id"]]) // Only add 1 to the users if they are not banned and are confirmed.
				$userRank += 1;
			}

			// Total score. Should be longlong,
			// but requires 64bit PHP. Memes incoming.
			$userScore = $userStats["ranked_score_std"];
			$userPlaycount = $userStats["playcount_std"];
			// Default to std. Will fix this maybe later.
			// x01: Std
			// x02: Taiko
			// x03: Ctb
			// x04: Mania
			$userGamemode = "\x00";
			$userAccuracy = $userStats["avg_accuracy_std"];
			$userPP = 0;	// Tillerino is sad
		
			// Output variable because multiple outGz are bugged.
			$output = "";
			
			// Standard stuff (login OK, lock client, memes etc)
			$output .= "\x5C\x00\x00\x04\x00\x00\x00\x00\x00\x00\x00\x05\x00\x00\x04\x00\x00\x00";
			// User ID
			$output .= pack("L", $userID);
			// More standard stuff
			$output .= "\x4B\x00\x00\x04\x00\x00\x00\x13\x00\x00\x00\x47\x00\x00";
			
			// Supporter/QAT/Friends stuff
			$output .= "\x04\x00\x00\x00".$userSupporter."\x00\x00\x00";

			// Online Friends
			/*$output .= "\x48\x00\x00\x0A\x00\x00\x00\x02\x00";
			$output .= pack("L", 100);
			$output .= pack("L", 100);
			$output .= "";*/
			
			// Other stuff
			$output .= "\x53\x00\x00";
			// Something strange related to username length. Wtf peppy?
			$output .= pack("L", 21+strlen($username));

			// User panel data
			// User ID
			$output .= pack("L", $userID);
			// Username
			$output .= binStr($username);
			// Timezone (maybe?)
			$output .= "\x18";
			// Country
			$output .= pack("L", $userCountry);
			$output .= "\x00\x00\x00\x00\x00\x00";
			// Rank
			$output .= pack("L", $userRank);
			$output .= "\x0B\x00\x00\x2E\x00\x00\x00";
			$output .= pack("L", $userID);
			$output .= "\x00\x00\x00\x00\x00\x00\x00";
			// Game mode
			$output .= $userGamemode;
			$output .= "\x00\x00\x00\x00";
			// Score
			$output .= pack("L", $userScore);
			$output .= "\x00\x00\x00\x00";
			// Accuracy (0.1337 = 13,37%)
			$output .= pack("f", $userAccuracy/100);
			// Playcount
			$output .= pack("L", $userPlaycount);
			// Level progress (will add this later)
			$output .= "\x00\x00\x00\x00";
			$output .= "\x00\x00\x00\x00";
			// Rank
			$output .= pack("L", $userRank);
			// PP
			$output .= pack("S", $userPP);
			
			// Online users info
			// Some flags
			$output .= "\x53\x00\x00";
			// Something related to name length,
			// if not correct user won't be shown
			$output .= pack("L", 21+strlen("FokaBot"));
			// User ID
			$output .= pack("L", 999);
			// Username
			$output .= binstr("FokaBot");
			// Other flags
			$output .= "\x18\x01\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";

			/*// Same as above, kinda
			$output .= "\x00\x00\x53\x00\x00\x1B\x00\x00\x00";
			$output .= pack("L", 333);
			$output .= binStr("user1");
			$output .= "\x18\x01\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";

			 Same as above
			$output .= "\x00\x00\x53\x00\x00\x1D\x00\x00\x00";
			$output .= pack("L", 381);
			$output .= binStr("user2");
			$output .= "\x18\x01\x00\x00\x00\x00\x00\x00\x00\x00\x00\x34\x00";*/

			// Channel join, maybe?
			$output .= "\x00\x00\x60\x00\x00\x0A\x00\x00\x00\x02\x00\x00\x00\x00\x00";
			$output .= pack("L", $userID);
			$output .= "\x59\x00\x00\x04\x00\x00\x00\x00\x00\x00\x00\x40\x00\x00\x06\x00\x00\x00";
			$output .= binStr("#osu");

			// Channels info packets
			$output .= "\x41\x00\x00\x16\x00\x00\x00";
			// Channel name
			$output .= binStr("#osu");
			// Channel description
			$output .= binStr("Fuck bancho");
			// Connected users
			$output .= pack("S", 1337);
			$output .= "\x00";

			// Default login messages
			$messages = explode("\r\n", current($GLOBALS["db"]->fetch("SELECT value_string FROM bancho_settings WHERE name = 'login_messages'")));
			foreach ($messages as $message) {
				$messageData = explode('|', $message);
				$output .= sendMessage($messageData[0], "#osu", $messageData[1], false);
			}

			// Restricted meme message
			if (current($GLOBALS["db"]->fetch("SELECT value_int FROM bancho_settings WHERE name = 'restricted_joke'")) == 1)
				$output .= sendMessage("FokaBot", $username, "Your account is currently in restricted mode. Just kidding xd WOOOOOOOOOOOOOOOOOOOOOOO", false);

			// Login notification
			$msg = current($GLOBALS["db"]->fetch("SELECT value_string FROM bancho_settings WHERE name = 'login_notification'"));
			if ($msg != "")
				$output .= sendNotification($msg);

			/* Add some memes
			$output .= sendMessage("BanchoBot", $username, "Wtf? Who is FokaBot? Someone is trying to take my place? I'll restrict his account, give me a minute...", true);
			$output .= sendMessage("peppy", $username, "Fuck a donkey.", true);
			$output .= sendMessage("Loctav", $username, "So you are playing on ripple? I'll restrict your osu! account. Fuck you.", true);
			$output .= sendMessage("Cookiezi", $username, "ㅋㅋㅋㅋㅋ", true);
			$output .= sendMessage("Tillerino", $username, "Hello, I'm Tillerino, the PP wizard. Unfortunately this bot and PPs don't exist on Ripple yet :(", true);

			// #osu memes
			$output .= sendMessage("peppy", "#osu", "Who the fuck is FokaaBot?", false);
			$output .= sendMessage("BanchoBot", "#osu", "Peppy-sama!! He's trying replace me!", false);
			$output .= sendMessage("FokaBot", "#osu", "Che schifo peppy xd", false);
			$output .= sendMessage("peppy", "#osu", "!moderated on", false);
			$output .= sendMessage("BanchoBot", "#osu", "Moderated mode activated!", false);
			$output .= sendMessage("peppy", "#osu", "Fucktards.", false);*/

			// Output everything
			outGz($output);
		}
		else
		{
			// Other packets
			$output = "";
			
			// Main menu icon
			$msg = current($GLOBALS["db"]->fetch("SELECT value_string FROM bancho_settings WHERE name = 'menu_icon'"));
			if ($msg != "")
			{
				$output .= "\x4C\x00\x00\x3D\x00\x00\x00";
				$output .= binStr($msg);
			}
			
			outGz($output);
			
			// Welcome to ripple message
			/*$msg = "Welcome to Ripple!";
			$output .= "\x18\x00\x00";
			$output .= pack("L", strlen($msg)+2);
			$output .= binStr($msg);*/
			
			// Test message
			/*$output = "";
			$output .= sendMessage("peppy", "#osu", "[DEBUG] Pong", true);*/
		}
	}
?>