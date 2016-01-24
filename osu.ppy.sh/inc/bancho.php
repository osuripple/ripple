<?php

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
	 * outputMessage
	 * Send a message to chat
	 *
	 * @param (string) ($from) From username
	 * @param (string) ($to) To username or channel
	 * @param (string) ($msg) Actual message
	 * @return (string)
	 */
	function outputMessage($from, $to, $msg)
	{
		$r = "";
		$r .= "\x07\x00\x00";
		$r .= pack("L", strlen($msg)+strlen($from)+strlen($to)+4+6);
		$r .= binStr($from);
		$r .= binStr($msg);
		$r .= binStr($to);
		$r .= "\x55\x01\x00\x00";	// User id
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

	// Generate a random Ripple Tatoe Token
	function generateToken()
	{
		return uniqid("rtt");
	}

	// Save a token in bancho_tokens
	function saveToken($t, $uid)
	{
		// Get latest message id, so we don't send messages sent before this user logged in
		$lm = $GLOBALS["db"]->fetch("SELECT id FROM bancho_messages ORDER BY id DESC");
		if (!$lm)
			$lm = 0;
		else
			$lm = current($lm);

		// Save token, latest action time and latest message id
		$GLOBALS["db"]->execute("INSERT INTO bancho_tokens (token, osu_id, latest_message_id, kicked) VALUES (?, ?, ?, 0)", array($t, $uid, $lm));
	}

	// Delete all tokens for $uid user, except the current one ($ct)
	function deleteOldTokens($uid, $ct)
	{
		$GLOBALS["db"]->execute("DELETE FROM bancho_tokens WHERE osu_id = ? AND token != ?", array($uid, $ct));
	}

	// Get user id from token
	// Return user id if success
	// Return -1 if token not found
	function getUserIDFromToken($t)
	{
		$query = $GLOBALS["db"]->fetch("SELECT osu_id FROM bancho_tokens WHERE token = ?", array($t));
		if ($query)
			return current($query);
		else
			return -1;
	}

	// Returns an user panel packet from user id
	function userPanel($uid)
	{
		// Get user data and stats
		$username = getUserUsername($uid);
		$userStats = $GLOBALS["db"]->fetch("SELECT * FROM users_stats WHERE username = ?", array($username));
		$userID = getUserOsuID($username);
		$userCountry = 108;

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

		// Packet start
		$output = "";
		$output .= "\x53\x00\x00";

		// 127 uint length meme thing
		$output .= pack("L", 21+strlen($username));

		// User panel data
		// User ID
		$output .= pack("L", $userID);
		// Username
		$output .= binStr($username);
		// Timezone
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

		// Return the packet
		return $output;
	}

	// Not used
	/*function updateLatestActionTime($uid)
	{
		// Add token check there
		$GLOBALS["db"]->execute("UPDATE bancho_tokens SET latest_action_time = ? WHERE osu_id = ?", array(time(), $uid));
	}

	function getLatestActionTime($uid)
	{
		$q = $GLOBALS["db"]->fetch("SELECT latest_action_time FROM bancho_tokens WHERE osu_id = ?", array($uid));
		if($q)
			return current($q);
		else
			return 0;
	}*/

	// Set $uid's message id to $mid
	function updateLatestMessageID($uid, $mid)
	{
		$GLOBALS["db"]->execute("UPDATE bancho_tokens SET latest_message_id = ? WHERE osu_id = ?", array($mid, $uid));
	}

	// Get user latest message id
	function getLatestMessageID($uid)
	{
		return current($GLOBALS["db"]->fetch("SELECT latest_message_id FROM bancho_tokens WHERE osu_id = ?", array($uid)));
	}

	// Return all the unreceived messages for a user
	// Get everything sent after the latest message
	// Ignore his own messages
	function getUnreceivedMessages($uid)
	{
		return $GLOBALS["db"]->fetchAll("SELECT * FROM bancho_messages WHERE id > ? AND msg_from != ?", array(getLatestMessageID($uid), $uid));
	}

	// Adds a message to DB
	function addMessageToDB($fuid, $to, $msg)
	{
		$GLOBALS["db"]->execute("INSERT INTO bancho_messages (`msg_from`, `msg_to`, `msg`, `time`) VALUES (?, ?, ?, ?)", array($fuid, $to, $msg, time()));
	}

	// Reads a binary string.
	// Works with messages, might not work with other packets
	// $s is the input packet, $start is the position of \x0B
	function readBinStr($s, $start)
	{
		// Make sure this is a string
		if($s[$start] != "\x0B")
			return false;

		// is a string, read length (buggy)
		// $len = intval(unpack("C",$s[$start+1])[1]);

		$str = "";
		$i = $start+2;
		while(isset($s[$i]) && $s[$i] != "\x0B")
		{
			// Read characters until a new \x0B (new string) or packet end
			$str .= $s[$i];
			$i++;
		}

		// Return the string
		return $str;
	}

	function fokaBotCommands($f, $m)
	{
		switch($m)
		{
			// Faq commands
			case checkSubStr($m, "!faq rules"): addMessageToDB(999, "#osu", "Please make sure to check (Ripple's rules)[http://ripple.moe/?p=23]."); break;
			case checkSubStr($m, "!faq swearing"): addMessageToDB(999, "#osu", "Please don't abuse swearing."); break;
			case checkSubStr($m, "!faq spam"): addMessageToDB(999, "#osu", "Please don't spam."); break;
			case checkSubStr($m, "!faq offend"): addMessageToDB(999, "#osu", "Please don't offend other players."); break;
			case checkSubStr($m, "!report"): addMessageToDB(999, "#osu", "Report command is not here yet."); break;

			case checkSubStr($m, "!silence"):
			{
				try
				{
					// Make sure we are an admin
					if (!checkAdmin($f))
						throw new Exception("Plz no akerino.");

					// Explode message
					$m = explode(" ", $m);

					// Check command parameters count
					if (count($m) < 4)
						throw new Exception("Invalid syntax. Syntax: !silence <username> <count> <unit (s/m/h/d)> <reason>");

					// Get command parameters
					$who = $m[1];
					$num = $m[2];
					$unit = $m[3];
					$reason = implode(" ", array_slice($m, 4));

					// Make sure the user exists
					if (!checkUserExists($who))
						throw New Exception("Invalid user");

					// Get unit (s/m/h/d)
					switch($unit)
					{
						case 's': $base = 1; break;
						case 'm': $base = 60; break;
						case 'h': $base = 3600; break;
						case 'd': $base = 86400; break;
						default: $base = 1; break;
					}
					
					// Calculate silence end time
					$end = $num*$base;

					// Make sure the user has lower rank than us
					if (getUserRank($who) >= getUserRank($f))
						throw new Exception("You can't silence that user.");

					// Silence and kick user
					silenceUser(getUserOsuID($who), time()+$end, $reason);
					kickUser(getUserOsuID($who));

					// Send FokaBot message
					throw New Exception($who." has been silenced for the following reason: ".$reason);
				}
				catch (Exception $e)
				{
					addMessageToDB(999, "#osu", $e->getMessage());
				}
			}
			break;

			case checkSubStr($m, "!kick"):
			{
				try
				{
					// Make sure we are an admin
					if (!checkAdmin($f))
						throw new Exception("Pls no akerino");

					// Explode message
					$m = explode(" ", $m);

					// Check parameter count
					if (count($m) < 2)
						throw new Exception("Invalid syntax. Syntax: !kick <username>");

					// Get command parameters
					$who = $m[1];

					// Make sure the user exists
					if (!checkUserExists($who))
						throw new Exception("Invalid user.");

					// Make sure the user has lower rank than us
					if (getUserRank($who) >= getUserRank($f))
						throw new Exception("You can't kick that user.");

					// Kick client
					kickUser(getUserOsuID($who));

					// User kicked!
					throw new Exception($who." has been kicked from the server.");
				}
				catch (Exception $e)
				{
					addMessageToDB(999, "#osu", $e->getMessage());
				}
			}
			break;

			case checkSubStr($m, "!moderated on"):
			{
				// Admin only command
				if (checkAdmin($f))
				{
					// Enable moderated mode
					setChannelStatus("#osu", 2);
					addMessageToDB(999, "#osu", "This channel is now in moderated mode!");
				}
			}
			break;

			case checkSubStr($m, "!moderated off"):
			{
				// Admin only command
				if (checkAdmin($f))
				{
					// Disable moderated mode
					setChannelStatus("#osu", 1);
					addMessageToDB(999, "#osu", "This channel is no longer in moderated mode!");
				}
			}
			break;
		}
	}

	// Channel mode:
	// 0: doesn't exists
	// 1: normal
	// 2: moderated
	function getChannelStatus($c)
	{
		// Make sure the channel exists
		$q = $GLOBALS["db"]->fetch("SELECT status FROM bancho_channels WHERE name = ?", array($c));

		// Return channel status
		if ($q)
			return current($q);
		else
			return 0;
	}

	function setChannelStatus($c, $s)
	{	
		$GLOBALS["db"]->execute("UPDATE bancho_channels SET status = ? WHERE name = ?", array($s, $c));
	}

	function checkKicked($t)
	{
		$q = $GLOBALS["db"]->fetch("SELECT kicked FROM bancho_tokens WHERE token = ?", array($t));
		if (!$q)
			return false;
		else
			return (bool)current($q);
	}

	function getSilenceEnd($uid)
	{
		return current($GLOBALS["db"]->fetch("SELECT silence_end FROM users WHERE osu_id = ?", array($uid)));
	}

	function silenceUser($uid, $se, $sr)
	{
		$GLOBALS["db"]->execute("UPDATE users SET silence_end = ?, silence_reason = ? WHERE osu_id = ?", array($se, $sr, $uid));
	}

	function isSlienced($uid)
	{
		if (getSilenceEnd($uid) <= time())
			return false;
		else
			return true;
	}
	
	function kickUser($uid)
	{
		// Make sure the token exists
		$q = $GLOBALS["db"]->fetch("SELECT id FROM bancho_tokens WHERE osu_id = ?", array($uid));

		// Kick if token found
		if ($q)
			$GLOBALS["db"]->execute("UPDATE bancho_tokens SET kicked = 1 WHERE osu_id = ?", array($uid));
	}

	function checkUserExists($u)
	{
		return $GLOBALS["db"]->fetch("SELECT id FROM users WHERE username = ?", $u);
	}

	function checkSpam($uid)
	{
		$q = $GLOBALS["db"]->fetch("SELECT COUNT(*) FROM bancho_messages WHERE msg_from = ? AND time >= ? AND time <= ?", array($uid, time()-10, time()) );
		if ($q)
		{
			if (current($q) >= 7)
				return true;
			else
				return false;
		}
		else
		{
			return false;
		}
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

		// Global variables
		$token = "";

		// Generate token if first packet
		if(!isset($_SERVER["HTTP_OSU_TOKEN"]))
		{
			// We don't have a token, generate it
			$token = generateToken();
			header("cho-token: ".$token);
		}
		else
		{
			// We have a token, use it
			$token = $_SERVER["HTTP_OSU_TOKEN"];
			header("cho-token: ".$_SERVER["HTTP_OSU_TOKEN"]);
		}

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

		// Check kick
		if(isset($_SERVER["HTTP_OSU_TOKEN"]) && checkKicked($_SERVER["HTTP_OSU_TOKEN"]))
		{
			$output = "";
			$output .= sendNotification("You have been kicked from the server. Please login again.");
			$output .= "\x05\x00\x00\x04\x00\x00\x00\xFF\xFF\xFF\xFF";
			outGz($output);
			die();
		}

		// Get data
		$data = file('php://input');

		// Check if this is the first packet
		if(!isset($_SERVER["HTTP_OSU_TOKEN"]))
		{
			try
			{
				// Get provided username and password.
				// We need to remove last character because it's new line
				// Fuck php
				$username = substr($data[0], 0, -1);
				$password = substr($data[1], 0, -1);

				// Check user/password
				if (!checkOsuUser($username, $password)) {
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
			// Update latest activity
			updateLatestActivity($username);

			// Get user data and stats
			$userData = $GLOBALS["db"]->fetch("SELECT * FROM users WHERE username = ?", array($username));
			$userStats = $GLOBALS["db"]->fetch("SELECT * FROM users_stats WHERE username = ?", array($username));

			// Get user id
			$userID = $userData["osu_id"];

			// Delete old token (if exist) and save the new one
			saveToken($token, $userID);
			deleteOldTokens($userID, $token);

			// Big meme here. Username is case-insensitive
			// but if we type it with wrong uppercase thing
			// there are memes in the userpanel. We don't
			// want it. Get the right username.
			$username = getUserUsername($userID);

			// Get silence time
			$silenceTime = getSilenceEnd($userID)-time();

			// Reset silence time if silence ended
			if ($silenceTime < 0)
				$silenceTime = 0;

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

			// Output variable because multiple outGz are bugged.
			$output = "";

			// Standard stuff (login OK, lock client, memes etc)
			$output .= "\x5C\x00\x00\x04";
			$output .= "\x00\x00\x00";
			$output .= pack("L", $silenceTime);
			$output .= "\x05\x00\x00\x04\x00\x00\x00";
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

			// Output user panel stuff
			$output .= userPanel($userID);

			// Online users info
			// Packet start
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
				$output .= outputMessage($messageData[0], "#osu", $messageData[1]);
			}

			// Restricted meme message
			if (current($GLOBALS["db"]->fetch("SELECT value_int FROM bancho_settings WHERE name = 'restricted_joke'")) == 1)
				$output .= outputMessage("FokaBot", $username, "Your account is currently in restricted mode. Just kidding xd WOOOOOOOOOOOOOOOOOOOOOOO");

			// Login notification
			$msg = current($GLOBALS["db"]->fetch("SELECT value_string FROM bancho_settings WHERE name = 'login_notification'"));
			if ($msg != "")
				$output .= sendNotification($msg);

			/* Add some memes
			$output .= outputMessage("BanchoBot", $username, "Wtf? Who is FokaBot? Someone is trying to take my place? I'll restrict his account, give me a minute...", true);
			$output .= outputMessage("peppy", $username, "Fuck a donkey.", true);
			$output .= outputMessage("Loctav", $username, "So you are playing on ripple? I'll restrict your osu! account. Fuck you.", true);
			$output .= outputMessage("Cookiezi", $username, "ㅋㅋㅋㅋㅋ", true);
			$output .= outputMessage("Tillerino", $username, "Hello, I'm Tillerino, the PP wizard. Unfortunately this bot and PPs don't exist on Ripple yet :(", true);

			// #osu memes
			$output .= outputMessage("peppy", "#osu", "Who the fuck is FokaaBot?", false);
			$output .= outputMessage("BanchoBot", "#osu", "Peppy-sama!! He's trying replace me!", false);
			$output .= outputMessage("FokaBot", "#osu", "Che schifo peppy xd", false);
			$output .= outputMessage("peppy", "#osu", "!moderated on", false);
			$output .= outputMessage("BanchoBot", "#osu", "Moderated mode activated!", false);
			$output .= outputMessage("peppy", "#osu", "Fucktards.", false);*/

			// Output everything
			outGz($output);
		}
		else
		{
			// Other packets
			$output = "";

			// Get memes
			$userID = getUserIDFromToken($token);
			$username = getUserUsername($userID);

			// Check if user has sent a message (packet starts with \x01\x00\x00)
			// if so, add it to DB
			if ($data[0][0] == "\x01" && $data[0][1] == "\x00" && $data[0][2] == "\x00")
			{
				// Check channel status and silence
				if ((getChannelStatus("#osu") == 1 && !isSlienced($userID)) || checkAdmin($username))
				{
					// Channel is not in moderated mode and we are not silenced, or we are admin
					$msg = readBinStr($data[0], 9);
					if (strlen($msg) > 0)
					{
						addMessageToDB($userID,"#osu",$msg);

						// Check if this message has triggered a fokabot command
						fokaBotCommands($username, $msg);

						// Anti spam
						if (checkSpam($userID))
						{
							addMessageToDB(999, "#osu", $username." has been silenced (FokaBot spam protection)");
							silenceUser($userID, time()+300, "Spamming (FokaBot spam protection)");
							kickUser($userID);
						}
					}
					else
					{
						addMessageToDB(999, "#osu", "Error while sending your message. Please try again.");
					}
				}
			}

			// Send updated userpanel if we've submitted a score
			// (packet starts with \x00\x00\x00\x0E\x00\x00\x00)
			if ($data[0][0] == "\x00" && $data[0][1] == "\x00" && $data[0][2] == "\x00" && $data[0][3] == "\x0E" && $data[0][4] == "\x00" && $data[0][5] == "\x00" && $data[0][6] == "\x00")
				$output .= userPanel($userID);

			// Output unreceived messages if needed
			$messages = getUnreceivedMessages($userID);
			$last = 0;
			if ($messages)
			{
				foreach ($messages as $message) {
					$output .= outputMessage(getUserUsername($message["msg_from"]), "#osu", $message["msg"]);
					$last = $message["id"];
				}
			}

			// If we have received some messages, update our latest message ID
			if ($last != 0)
				updateLatestMessageID($userID, $last);

			// Output everything
			outGz($output);


			// Main menu icon
			/*$msg = current($GLOBALS["db"]->fetch("SELECT value_string FROM bancho_settings WHERE name = 'menu_icon'"));
			if ($msg != "")
			{
				$output .= "\x4C\x00\x00\x3D\x00\x00\x00";
				$output .= binStr($msg);
			}*/

			// Welcome to ripple message
			/*$msg = "Welcome to Ripple!";
			$output .= "\x18\x00\x00";
			$output .= pack("L", strlen($msg)+2);
			$output .= binStr($msg);*/

			// Test message
			/*$output = "";
			$output .= addMessageToDB("peppy", "#osu", "[DEBUG] Pong", true);*/
		}
	}
?>
