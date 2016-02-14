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
		$r .= "\x0B".pack("c", strlen($str));
		$r .= $str;
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
		$r .= pack("L", getUserOsuID($from));
		return $r;
	}


	/*
	 * outputChannel
	 * Output a channel info message
	 *
	 * @param (string) ($name) Channel name
	 * @param (string) ($desc) Channel description
	 * @param (string) ($users) Connected users
	 * @return (string)
	 */
	function outputChannel($name, $desc, $users)
	{
		$r = "";
		$r .= "\x41\x00\x00";
		$r .= pack("L", strlen($name)+strlen($desc)+2+4);
		$r .= binStr($name);
		$r .= binStr($desc);
		$r .= pack("S", 1337);
		return $r;
	}


	/*
	 * outputNotification
	 * Send a notification to client
	 * Use \\n for new line
	 *
	 * @param (string) ($msg) Notification message
	 * @return (string)
	 */
	function outputNotification($msg)
	{
		$r = "";
		$r .= "\x18\x00\x00";
		$r .= pack("L", strlen($msg)+2);
		$r .= binStr($msg);
		return $r;
	}


	/*
	 * generateToken
	 * Generate a random ripple token
	 *
	 * @return (string)
	 */
	function generateToken()
	{
		return uniqid("rtt");
	}


	/*
	 * saveToken
	 * Save a ripple token in db
	 *
	 * @param (string) ($t) Token string. Use generateToken() to get one.
	 * @param (int) ($uid) User id
	 */
	function saveToken($t, $uid)
	{
		// Get latest message id, so we don't send messages sent before this user logged in
		$lm = $GLOBALS["db"]->fetch("SELECT id FROM bancho_messages ORDER BY id DESC LIMIT 1");
		if (!$lm)
			$lm = 0;
		else
			$lm = current($lm);

		// Do the same for private messages
		$lpm = $GLOBALS["db"]->fetch("SELECT id FROM bancho_private_messages ORDER BY id DESC LIMIT 1");
		if (!$lpm)
			$lpm = 0;
		else
			$lpm = current($lpm);

		// Save token with latest action time and latest message id
		$GLOBALS["db"]->execute("INSERT INTO bancho_tokens (token, osu_id, latest_message_id, latest_private_message_id, latest_packet_time, latest_heavy_packet_time, joined_channels, game_mode, action, action_text, kicked) VALUES (?, ?, ?, ?, ?, 0, '', 0, 0, '', 0)", array($t, $uid, $lm, $lpm, time()));
	}


	/*
	 * deleteOldTokens
	 * Delete all tokens from older session but the current one
	 *
	 * @param (int) ($uid) User id
	 * @param (string) ($ct) Current token
	 */
	function deleteOldTokens($uid, $ct)
	{
		$GLOBALS["db"]->execute("DELETE FROM bancho_tokens WHERE osu_id = ? AND token != ?", array($uid, $ct));
	}


	/*
	 * getUserIDFromToken
	 * Get user id from token
	 *
	 * @param (string) ($t) Token
	 * @return (int) user id if success, -1 if not found
	 */
	function getUserIDFromToken($t)
	{
		$query = $GLOBALS["db"]->fetch("SELECT osu_id FROM bancho_tokens WHERE token = ?", array($t));
		if ($query)
			return current($query);
		else
			return -1;
	}


	/*
	 * userPanel
	 * Return userpanel packet for $uid user
	 *
	 * @param (int) ($uid) User ID
	 * @param (int) ($gm) Game mode (0 std,1 taiko,2 ctb,3 mania)
	 * @return (string) UP Packet
	 */
	function userPanel($uid)
	{
		// Get mode for DB
		$gm = getGameMode($uid);
		switch($gm)
		{
			default: $modeForDB = "std"; break;
			case 1: $modeForDB = "taiko"; break;
			case 2: $modeForDB = "ctb"; break;
			case 3: $modeForDB = "mania"; break;
		}

		// Get user data
		$username = getUserUsername($uid);
		$rank = getUserRank($username);
		$userCountry = 108;

		// Username color
		switch($rank)
		{
			case 1: $userColor = "\x00"; break;	// Normal user
			case 2: $userColor = "\x04"; break;	// Supporter, yellow
			case 3: $userColor = "\x06"; break;	// Mod, red
			case 4: $userColor = "\x10"; break;	// Admin, light blue
		}

		// Fokabot is red
		if($username == "FokaBot")
			$userColor = "\x06";

		// Get game rank
		$gameRank = Leaderboard::GetUserRank($uid, $modeForDB);

		// Packet start
		$output = "";
		$output .= "\x53\x00\x00";

		// 127 uint length meme thing
		$output .= pack("L", 21+strlen($username));

		// User panel data
		// User ID
		$output .= pack("L", $uid);
		// Username
		$output .= binStr($username);
		// Timezone
		$output .= "\x19";
		// Country
		$output .= pack("c", $userCountry);
		$output .= $userColor;
		$output .= "\x00\x00";
		$output .= "\x00\x00\x00\x00";
		$output .= "\x00\x00";
		// Game rank
		$output .= pack("L", $gameRank);

		// Return the packet
		return $output;
	}


	function userStats($uid)
	{
		// Get mode for DB
		$gm = getGameMode($uid);
		switch($gm)
		{
			default: $modeForDB = "std"; break;
			case 1: $modeForDB = "taiko"; break;
			case 2: $modeForDB = "ctb"; break;
			case 3: $modeForDB = "mania"; break;
		}

		// Get user stats
		$userStats = $GLOBALS["db"]->fetch("SELECT * FROM users_stats WHERE osu_id = ?", array($uid));
		$userScore = $userStats["ranked_score_".$modeForDB];
		$userPlaycount = $userStats["playcount_".$modeForDB];
		$userAccuracy = $userStats["avg_accuracy_".$modeForDB];
		$totalScore = $userStats["total_score_".$modeForDB];
		$userPP = 0;	// Tillerino is sad
		$action = getAction($uid);
		$actionText = getActionText($uid);

		// Get game rank
		$gameRank = Leaderboard::GetUserRank($uid, $modeForDB);

		// User stats packet
		$output = "";
		$output .= "\x0B\x00\x00";
		$output .= !empty($actionText) ? pack("L", 48+strlen($actionText)+strlen("md5here")) : pack("L", 46);
		$output .= pack("L", $uid);

		// Other flags
		// User status (idle, afk, playing etc)
		//x00: Idle,
		//x01: Afk,
		//x02: Playing,
		//x03: Editing,
		//x04: Modding,
		//x05: Multiplayer,
		//x06: Watching,
		//x07: Unknown,
		//x08: Testing,
		//x09: Submitting,
		//x0A: (10) Paused,
		//x0B: (11) Lobby,
		//x0C: (12) Multiplaying,
		//x0D: (13) OsuDirect
		//$output .= pack("c", getAction($uid));
		//$output .= "\x00\x00\x00\x00\x00\x00";
		$output .= pack("c", $action);
		if (!empty($actionText))
		{
			$output .= binStr($actionText);
			$output .= binStr("md5here");
			$output .= "\x00\x00\x00\x00";
		}
		else
		{
			$output .= "\x00\x00\x00\x00\x00\x00";
		}

		// Game mode
		// x00: Std
		// x01: Taiko
		// x02: Ctb
		// x03: Mania
		$output .= pack("c", $gm);
		$output .= "\x00\x00\x00\x01";
		// Score
		$output .= pack("L", $userScore);
		$output .= "\x00\x00\x00\x00";
		// Accuracy (0.1337 = 13,37%)
		$output .= pack("f", $userAccuracy/100);
		// Playcount
		$output .= pack("L", $userPlaycount);
		// Level progress
		$output .= pack("L", $totalScore);
		$output .= "\x00\x00\x00\x00";
		// Rank
		$output .= pack("L", $gameRank);
		// PP
		$output .= pack("S", $userPP);

		return $output;
	}


	/*
	 * getAction
	 * Get action (idle, playing etc) for $uid user
	 *
	 * @param (int) ($uid) User ID
	 * @return (int) Action code
	 */
	function getAction($uid)
	{
		return current($GLOBALS["db"]->fetch("SELECT action FROM bancho_tokens WHERE osu_id = ?", array($uid)));
	}


	/*
	 * setAction
	 * Sets action (idle, playing etc) for $uid user
	 *
	 * @param (int) ($uid) User ID
	 * @param (int) ($a) Action ID
	 */
	function setAction($uid, $a)
	{
		current($GLOBALS["db"]->execute("UPDATE bancho_tokens SET action = ? WHERE osu_id = ?", array($a, $uid)));
	}


	/*
	 * getActionText
	 * Get action text for $uid user
	 *
	 * @param (int) ($uid) User ID
	 * @return (string) Action text
	 */
	function getActionText($uid)
	{
		return current($GLOBALS["db"]->fetch("SELECT action_text FROM bancho_tokens WHERE osu_id = ?", array($uid)));
	}


	/*
	 * setActionText
	 * Sets action (idle, playing etc) for $uid user
	 *
	 * @param (int) ($uid) User ID
	 * @param (string) ($s) Action text
	 */
	function setActionText($uid, $s)
	{
		current($GLOBALS["db"]->execute("UPDATE bancho_tokens SET action_text = ? WHERE osu_id = ?", array($s, $uid)));
	}



	/*
	 * updateLatestMessageID
	 * Set $uid latest message id to $mid
	 *
	 * @param (int) ($uid) User ID
	 * @param (int) ($mid) New latest message ID
	 */
	function updateLatestMessageID($uid, $mid)
	{
		$GLOBALS["db"]->execute("UPDATE bancho_tokens SET latest_message_id = ? WHERE osu_id = ?", array($mid, $uid));
	}


	/*
	 * updateLatestPrivateMessageID
	 * Set $uid latest private message id to $mid
	 *
	 * @param (int) ($uid) User ID
	 * @param (int) ($mid) New latest private message ID
	 */
	function updateLatestPrivateMessageID($uid, $mid)
	{
		$GLOBALS["db"]->execute("UPDATE bancho_tokens SET latest_private_message_id = ? WHERE osu_id = ?", array($mid, $uid));
	}


	/*
	 * getLatestMessageID
	 * Get $uid latest message id
	 *
	 * @param (int) ($uid) User ID
	 * @return (int) Latest message ID
	 */
	function getLatestMessageID($uid)
	{
		return current($GLOBALS["db"]->fetch("SELECT latest_message_id FROM bancho_tokens WHERE osu_id = ?", array($uid)));
	}


	/*
	 * getGlobalLatestMessageID
	 * Get global latest message id
	 *
	 * @return (int) Latest message ID
	 */
	function getGlobalLatestMessageID()
	{
		$q = $GLOBALS["db"]->fetch("SELECT id FROM bancho_messages ORDER BY id DESC LIMIT 1");
		if ($q)
			return current($q);
		else
			return 0;
	}


	/*
	 * getLatestPrivateMessageID
	 * Get $uid latest private message id
	 *
	 * @param (int) ($uid) User ID
	 * @return (int) Latest private message ID
	 */
	function getLatestPrivateMessageID($uid)
	{
		return current($GLOBALS["db"]->fetch("SELECT latest_private_message_id FROM bancho_tokens WHERE osu_id = ?", array($uid)));
	}


	/*
	 * getUnreceivedMessages
	 * Return an array with unreceived messages for $uid users
	 * unreceived messages are those sent after the latest message ID
	 *
	 * @param (int) ($uid) User ID
	 * @return (array) Message array
	 */
	function getUnreceivedMessages($uid)
	{
		// Public messages array
		$public = array();

		// Get joined channels
		$joinedChannels = rtrim(current($GLOBALS["db"]->fetch("SELECT joined_channels FROM bancho_tokens WHERE osu_id = ?", array($uid))), ",");
		if(!empty($joinedChannels))
		{
			// If we've joined some channel, get unreceived messages for that channel and add them to $public
			$latestMessageID = getLatestMessageID($uid);
			$joinedChannels = explode(",", $joinedChannels);
			foreach ($joinedChannels as $channel) {
				$channelMessages = $GLOBALS["db"]->fetchAll("SELECT * FROM bancho_messages WHERE id > ? AND msg_to = ? AND msg_from_userid != ?", array($latestMessageID, $channel, $uid));
				$public = array_merge($public, $channelMessages);
			}
		}

		//$public = $GLOBALS["db"]->fetchAll("SELECT * FROM bancho_messages WHERE id > ? AND msg_from_userid != ?", array(getLatestMessageID($uid), $uid));

		// Get unreveived private messages
		$private = $GLOBALS["db"]->fetchAll("SELECT * FROM bancho_private_messages WHERE id > ? AND msg_to = ?", array(getLatestPrivateMessageID($uid), getUserUsername($uid)));

		// Return unreceived public and private messages
		return array("public" => $public, "private" => $private);
	}


	/*
	 * addMessageToDB
	 * Adds a message to DB
	 *
	 * @param (int) ($fuid) Sender user ID
	 * @param (string) ($to) Receiver user ID
	 * @param (string) ($msg) Message
	 * @param (bool) ($private) If true, add the message bancho_private_messages, otherwise add it to bancho_messages
	 */
	function addMessageToDB($fuid, $to, $msg, $private = false)
	{
		$table = $private ? "bancho_private_messages" : "bancho_messages";
		$GLOBALS["db"]->execute("INSERT INTO ".$table." (`msg_from_userid`, `msg_from_username`, `msg_to`, `msg`, `time`) VALUES (?, ?, ?, ?, ?)", array($fuid, getUserUsername($fuid), $to, $msg, time()));
	}


	/*
	 * readBinStr
	 * Reads a binary string
	 * Works with messages, might not work with other packets
	 *
	 * @param (array) ($s) The byte array. This script reads ONLY THE FIRST LINE
	 * @param (int) ($s) Start (\x0B byte position)
	 * @return (string) The string
	 */
	function readBinStr($s, $start)
	{
		// Make sure this is a string
		if($s[0][$start] != "\x0B")
			return false;

		/* Check if length is 10 (\x0A, new line char)
		if($s[0][$start+1] == "\x0A")
		{
			$start = -2;	// fuck php
			$source = $s[1];
		}
		else
		{
			$source = $s[0];
		}*/

		$source = $s[0];
		$str = "";
		$i = $start+2;
		while(isset($source[$i]) && $source[$i] != "\x0B")
		{
			// Read characters until a new \x0B (new string) or packet end
			$str .= $source[$i];
			$i++;
		}

		// Return the string
		return $str;
	}


	/*
	 * fokaBotCommands
	 * Check if a message triggers a fokabot command
	 * If so, add fokabot's response to DB
	 *
	 * @param (string) ($f) Sender username
	 * @param (string) ($c) Channel
	 * @param (string) ($m) Message
	 */
	function fokaBotCommands($f, $c, $m)
	{
		switch($m)
		{
			// Faq commands
			case checkSubStr($m, "!faq rules"): addMessageToDB(999, $c, "Please make sure to check (Ripple's rules)[http://ripple.moe/?p=23]."); break;
			case checkSubStr($m, "!faq swearing"): addMessageToDB(999, $c, "Please don't abuse swearing."); break;
			case checkSubStr($m, "!faq spam"): addMessageToDB(999, $c, "Please don't spam."); break;
			case checkSubStr($m, "!faq offend"): addMessageToDB(999, $c, "Please don't offend other players."); break;
			case checkSubStr($m, "!report"): addMessageToDB(999, $c, "Report command is not here yet."); break;

			case checkSubStr($m, "!heavy"):
			{
				if (checkAdmin($f))
				{
					updateLatestPacketTime(getUserOsuID($f), 0, true);
					addMessageToDB(999, $c, "Sending heavy packets to ".$f."...");
				}
			}
			break;

			// !roll command
			case checkSubStr($m, "!roll"):
			{
				// Explode message
				$m = explode(" ", $m);

				// Get command parameters
				if (isset($m[1]) && intval($m[1]))
					$max = $m[1];
				else
					$max = 100;

				// Generate number
				if ($max > PHP_INT_MAX)
					$num = "youareanidiot";
				else
					$num = rand(0, $max);

				// Output
				addMessageToDB(999, $c, $f." rolls ".$num." points!");
			}
			break;

			// !silence command
			// Kick and silence someone
			// Syntax: !silence <username> <count> <unit (s/m/h/d)> <reason>
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
					addMessageToDB(999, $c, $e->getMessage());
				}
			}
			break;

			// !kick command
			// Disconnect a user from the server
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
					addMessageToDB(999, $c, $e->getMessage());
				}
			}
			break;

			// !moderated on
			// Put a channel in moderated mode
			case checkSubStr($m, "!moderated on"):
			{
				// Admin only command
				if (checkAdmin($f))
				{
					// Enable moderated mode
					setChannelStatus($c, 2);
					addMessageToDB(999, $c, "This channel is now in moderated mode!");
				}
			}
			break;

			// !moderated off
			// Turn off moderated mode from a channel
			case checkSubStr($m, "!moderated off"):
			{
				// Admin only command
				if (checkAdmin($f))
				{
					// Disable moderated mode
					setChannelStatus($c, 1);
					addMessageToDB(999, $c, "This channel is no longer in moderated mode!");
				}
			}
			break;
		}
	}


	/*
	 * getChannelStatus
	 * Get channel status
	 *
	 * @param (string) ($c) Channel name
	 * @return (int) 0: Channel doesn't exist, 1: normal, 2: moderated
	 */
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


	/*
	 * setChannelStatus
	 * Set channel status
	 *
	 * @param (string) ($c) Channel name
	 * @param (int) ($s) Channel status. 1: normal, 2: moderated
	 */
	function setChannelStatus($c, $s)
	{
		$GLOBALS["db"]->execute("UPDATE bancho_channels SET status = ? WHERE name = ?", array($s, $c));
	}


	/*
	 * checkKicked
	 * Check if an user should be kicked from the server
	 *
	 * @param (string) ($t) Token
	 * @return (bool)
	 */
	function checkKicked($t)
	{
		$q = $GLOBALS["db"]->fetch("SELECT kicked FROM bancho_tokens WHERE token = ?", array($t));
		if (!$q)
			return false;
		else
			return (bool)current($q);
	}


	/*
	 * getSilenceEnd
	 * Get user silence end time
	 *
	 * @param (int) ($uid) User ID
	 * @return (int) silence end time
	 */
	function getSilenceEnd($uid)
	{
		return current($GLOBALS["db"]->fetch("SELECT silence_end FROM users WHERE osu_id = ?", array($uid)));
	}


	/*
	 * silenceUser
	 * Set new silence end and reason for $uid
	 *
	 * @param (int) ($uid) User ID
	 * @param (int) ($se) Silence end time
	 * @param (string) ($st) Silence reason
	 */
	function silenceUser($uid, $se, $sr)
	{
		$GLOBALS["db"]->execute("UPDATE users SET silence_end = ?, silence_reason = ? WHERE osu_id = ?", array($se, $sr, $uid));
	}


	/*
	 * isSilenced
	 * Check if someone is silenced
	 *
	 * @param (int) ($uid) User ID
	 * @return (bool)
	 */
	function isSilenced($uid)
	{
		if (getSilenceEnd($uid) <= time())
			return false;
		else
			return true;
	}


	/*
	 * kickUser
	 * Set kick status to 1 for $uid
	 *
	 * @param (int) ($uid) User ID
	 */
	function kickUser($uid)
	{
		// Make sure the token exists
		$q = $GLOBALS["db"]->fetch("SELECT id FROM bancho_tokens WHERE osu_id = ?", array($uid));

		// Kick if token found
		if ($q)
			$GLOBALS["db"]->execute("UPDATE bancho_tokens SET kicked = 1 WHERE osu_id = ?", array($uid));
	}


	/*
	 * checkSpam
	 * Check if $uid is spamming
	 *
	 * @param (int) ($uid) User ID
	 * @return (bool)
	 */
	function checkSpam($uid)
	{
		$q = $GLOBALS["db"]->fetch("SELECT COUNT(*) FROM bancho_messages WHERE msg_from_userid = ? AND time >= ? AND time <= ?", array($uid, time()-10, time()) );
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
	 * updateLatestPacketTime
	 * Set latest packet time for $uid
	 *
	 * @param (int) ($uid) User ID
	 * @param (int) ($t) New latest packet time
	 * @param (bool) ($heavy) If true, update both latest packet and heavy packet time
	 */
	function updateLatestPacketTime($uid, $t, $h = false)
	{
		// Make sure the token exists
		$q = $GLOBALS["db"]->fetch("SELECT id FROM bancho_tokens WHERE osu_id = ?", array($uid));

		// If the token exists, update latest packet time
		if ($q)
		{
			$GLOBALS["db"]->execute("UPDATE bancho_tokens SET latest_packet_time = ? WHERE osu_id = ?", array($t, $uid));
			if($h) $GLOBALS["db"]->execute("UPDATE bancho_tokens SET latest_heavy_packet_time = ? WHERE osu_id = ?", array($t, $uid));
		}
	}


	/*
	 * outputChannelJoin
	 * Output join channel packet if
	 * Check if $u can join that channel too
	 *
	 * @param (string) ($u) Username (for permissions stuff)
	 * @param (string) ($chan) Channel name
	 * @return (string) Channel join packet (and error message from fokabot if $u can't join $chan)
	 */
	function outputChannelJoin($u, $chan)
	{
		try
		{
			// Make sure the channel exists
			if (!channelExists($chan))
				throw new Exception($chan." channel doesn't exists");

			// Make sure the channel is public or we are admin
			if (getChannelPublicRead($chan) == 0 && getUserRank($u) < 3)
				throw new Exception("You are not allowed to join ".$chan);

			// Channel exists and is public read, join it
			$uid = getUserOsuID($u);
			$joinedChannels = current($GLOBALS["db"]->fetch("SELECT joined_channels FROM bancho_tokens WHERE osu_id = ?", array($uid))).$chan.",";
			$GLOBALS["db"]->execute("UPDATE bancho_tokens SET joined_channels = ? WHERE osu_id = ?", array($joinedChannels, $uid));

			// Update latest_message_id so we don't get messages sent before we join
			$mid = getGlobalLatestMessageID();
			updateLatestMessageID($uid, $mid);

			// Output join packet
			$output = "";
			$output .= "\x40\x00\x00";
			$output .= pack("L", strlen($chan)+2);
			$output .= binStr($chan);
			return $output;
		}
		catch (Exception $e)
		{
			return outputMessage("FokaBot", $u, $e->getMessage());
		}
	}


	/*
	 * partChannel
	 * Remove $channel from joined channels for $uid user
	 *
	 * @param (string) ($uid) User ID
	 * @param (string) ($channel) Channel name
	 */
	function partChannel($uid, $channel)
	{
		$joinedChannels = current($GLOBALS["db"]->fetch("SELECT joined_channels FROM bancho_tokens WHERE osu_id = ?", array($uid)));
		$joinedChannels = str_replace($channel.",", "", $joinedChannels);
		$GLOBALS["db"]->execute("UPDATE bancho_tokens SET joined_channels = ? WHERE osu_id = ?", array($joinedChannels, $uid));
	}


	/*
	 * getChannelPublicWrite
	 * Get public write status for $c channel
	 *
	 * @param (string) ($c) Channel name
	 * @return (int) Public write status (0/1)
	 */
	function getChannelPublicWrite($c)
	{
		// Check if channel exists
		$q = $GLOBALS["db"]->fetch("SELECT public_write FROM bancho_channels WHERE name = ?", array($c));
		if ($q)
			return current($q);	// Return public write value
		else
			return 0;			// Doesn't exist, no write thing
	}


	/*
	 * getChannelPublicRead
	 * Get public read status for $c channel
	 *
	 * @param (string) ($c) Channel name
	 * @return (int) Public read status (0/1)
	 */
	function getChannelPublicRead($c)
	{
		// Check if channel exists
		$q = $GLOBALS["db"]->fetch("SELECT public_read FROM bancho_channels WHERE name = ?", array($c));
		if ($q)
			return current($q);	// Return public read value
		else
			return 0;			// Doesn't exist, no read thing
	}


	/*
	 * channelExists
	 * Check if $c channel exists
	 *
	 * @param (string) ($c) Channel name
	 * @return (bool)
	 */
	function channelExists($c)
	{
		return $GLOBALS["db"]->fetch("SELECT id FROM bancho_channels WHERE name = ?", array($c));
	}


	/*
	 * outputOnlineUsers
	 * Output online users UPs
	 *
	 * @param (int) ($stats) If true, output both userpanel and userstats
	 * @return (string)
	 */
	function outputOnlineUsers($stats = false)
	{
		$output = "";
		$onlineUsers = $GLOBALS["db"]->fetchAll("SELECT osu_id,game_mode FROM bancho_tokens WHERE kicked = 0 AND latest_packet_time >= ? OR osu_id = 999", array(time()-120));
		foreach ($onlineUsers as $user)
		{
			$output .= userPanel($user["osu_id"]);
			if ($stats)
				$output .= userStats($user["osu_id"]);
		}

		return $output;
	}


	/*
	 * isHeavy
	 * Check if we should send an heavy packet
	 *
	 * @param (string) ($t) Token
	 * @return (bool)
	 */
	function isHeavy($t)
	{
		$t = $GLOBALS["db"]->fetch("SELECT latest_heavy_packet_time FROM bancho_tokens WHERE token = ?", array($t));
		if (abs(time()-current($t)) >= 30)
			return true;
		else
			return false;
	}


	/*
	 * outputFriends
	 * Output friends
	 *
	 * @param (string) ($friends) Friends list
	 * @return (string)
	 */
	function outputFriends($friends)
	{
		$output = "";
		if (empty($friends))
			return $output;

		// Get friend ids and count
		$friends = explode(",", $friends);
		$count = count($friends);

		// Packet code, packet length and friends count
		$output .= "\x48\x00\x00";
		$output .= pack("L", (4*$count)+2);
		$output .= pack("s", $count);

		// Friends user IDs
		foreach ($friends as $friend)
			$output .= pack("L", $friend);

		return $output;
	}


	/*
	 * setGameMode
	 * Set user gamemode for userpanel
	 *
	 * @param (int) ($uid) User ID
	 * @param (int) ($gm) Game mode (0,1,2,3)
	 */
	function setGameMode($uid, $gm)
	{
		$GLOBALS["db"]->execute("UPDATE bancho_tokens SET game_mode = ? WHERE osu_id = ?", array($gm, $uid));
	}


	/*
	 * getGameMode
	 * Get user gamemode for userpanel
	 *
	 * @param (int) ($uid) User ID
	 * @return (int) Game mode (0,1,2,3) Def is 0
	 */
	function getGameMode($uid)
	{
		$q = $GLOBALS["db"]->fetch("SELECT game_mode FROM bancho_tokens WHERE osu_id = ?", array($uid));
		if ($q)
			return current($q);
		else
			return 0;
	}


	/*
	 * outputOfflineUsers
	 * Output users that went offline
	 *
	 * @return (memes) Packet output
	 */
	function outputOfflineUsers()
	{
		$output = "";
		$offlineUsers = $GLOBALS["db"]->fetchAll("SELECT osu_id FROM bancho_tokens WHERE latest_packet_time < ? AND osu_id != 999", array(time()-120));
		foreach ($offlineUsers as $user)
		{
			// TODO: Delete token
			$output .= outputOffline($user["osu_id"]);
		}

		return $output;
	}


	/*
	 * outputOffline
	 * Output an offline packet
	 *
	 * @return (memes) Packet output
	 */
	function outputOffline($uid)
	{
		$output = "";
		$output .= "\x0C\x00\x00";	// Packet code
		$output .= pack("L", 5);	// Packet length
		$output .= pack("L", $uid);	// User ID
		$output .= "\x00";
		return $output;
	}


	/*
	 * banchoServer
	 * Main bancho """server""" function
	 */
	function banchoServer()
	{
		// Set token to nothing
		$token = "";

		// Generate token if this it the first packet
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

		// Check bancho maintenance
		if (checkBanchoMaintenance())
		{
			$output = "";
			$output .= outputNotification("Ripple's Bancho server is in manitenance mode.\\nCheck http://ripple.moe/ for more information.");
			$output .= "\x05\x00\x00\x04\x00\x00\x00\xFF\xFF\xFF\xFF";
			outGz($output);
			die();
		}

		// Check if we should be kicked
		if(isset($_SERVER["HTTP_OSU_TOKEN"]) && checkKicked($_SERVER["HTTP_OSU_TOKEN"]))
		{
			$output = "";
			$output .= outputNotification("You have been kicked from the server. Please login again.");
			$output .= "\x05\x00\x00\x04\x00\x00\x00\xFF\xFF\xFF\xFF";
			outGz($output);
			die();
		}

		// Get data
		// and fuck php, seriously.
		if(!isset($_SERVER["HTTP_OSU_TOKEN"]))
			$data = file('php://input');
		else
			$data = str_split(str_replace("\x0A", "\x00", file_get_contents('php://input')), 512);

		// Check if this is the first packet
		if(!isset($_SERVER["HTTP_OSU_TOKEN"]))
		{
			// First packet, login stuff
			try
			{
				// Get provided username and password.
				// We need to remove last character because it's new line
				// Fuck php
				$username = substr($data[0], 0, -1);
				$password = substr($data[1], 0, -1);
				$hardwareData = explode("|", substr($data[2], 0, -1));
				$hardwareHashes = explode(":", $hardwareData[3]);
				$output = "";

				// Check osu! version and osu!.exe md5 if set from RAP
				$clientVersions = current($GLOBALS["db"]->fetch("SELECT value_string FROM bancho_settings WHERE name = 'osu_versions'"));
				$clientMd5s = current($GLOBALS["db"]->fetch("SELECT value_string FROM bancho_settings WHERE name = 'osu_md5s'"));
				if (!empty($clientVersions) && !empty($clientMd5s))
				{
					$clientVersions = explode("|", $clientVersions);
					$clientMd5s = explode("|", $clientMd5s);
					if (!in_array($hardwareData[0], $clientVersions) || !in_array($hardwareHashes[0], $clientMd5s)) {
						$output .= outputNotification("You are not using the right version of osu!. Please make sure you are on Stable (fallback) branch and your client is updated.");
						$output .= outputNotification("To update the client, you need to turn the switcher OFF.");
						throw new Exception("\xFE");
					}
				}

				// Check user/password
				if (!PasswordHelper::CheckPass($username, $password)) {
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
				$output .= "\x05\x00\x00\x04\x00\x00\x00".$e->getMessage()."\xFF\xFF\xFF";
				outGz($output);
				die();
			}

			// Username, password and allowed are ok
			// Update latest activity on website
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

			// Get silence end time
			$silenceTime = getSilenceEnd($userID)-time();

			// Reset silence time if silence ended
			if ($silenceTime < 0)
				$silenceTime = 0;

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
			// We output this variable at the end
			$output = "";

			// Standard stuff (login OK, lock client etc)
			$output .= "\x5C\x00\x00\x04";
			$output .= "\x00\x00\x00";
			$output .= pack("L", $silenceTime);
			$output .= "\x05\x00\x00\x04\x00\x00\x00";
			// User ID
			$output .= pack("L", $userID);
			// More unknown but required stuff
			$output .= "\x4B\x00\x00\x04\x00\x00\x00\x13\x00\x00\x00\x47\x00\x00";

			// Supporter/QAT/(and Friends) stuff
			$output .= "\x04\x00\x00\x00".$userSupporter."\x00\x00\x00";

			// Online Friends
			$output .= outputFriends($userData["friends"]);

			// Output our userpanel
			$output .= userPanel($userID);

			// Output online users
			$output .= outputOnlineUsers(true);

			// Required memes
			$output .= "\x60\x00\x00\x0A\x00\x00\x00\x02\x00\x00\x00\x00\x00";
			$output .= pack("L", $userID);
			$output .= "\x59\x00\x00\x04\x00\x00\x00\x00\x00\x00\x00";

			// Channel join
			// the client asks to join #osu at login, so we must send a channel joined packet
			$output .= outputChannelJoin($username, "#osu");

			// Channels packets
			$channels = $GLOBALS["db"]->fetchAll("SELECT * FROM bancho_channels");
			foreach ($channels as $channel) {
				$output .= outputChannel($channel["name"], $channel["description"], 1337);
			}

			// Default login chat messages
			$messages = current($GLOBALS["db"]->fetch("SELECT value_string FROM bancho_settings WHERE name = 'login_messages'"));
			if ($messages != "")
			{
				$messages = explode("\r\n", $messages);
				foreach ($messages as $message) {
					$messageData = explode('|', $message);
					$output .= outputMessage($messageData[0], "#osu", $messageData[1]);
				}
			}

			// Restricted meme message if needed
			if (current($GLOBALS["db"]->fetch("SELECT value_int FROM bancho_settings WHERE name = 'restricted_joke'")) == 1)
				$output .= outputMessage("FokaBot", $username, "Your account is currently in restricted mode. Just kidding xd WOOOOOOOOOOOOOOOOOOOOOOO");

			// Login notification if needed
			$msg = current($GLOBALS["db"]->fetch("SELECT value_string FROM bancho_settings WHERE name = 'login_notification'"));
			if ($msg != "")
				$output .= outputNotification($msg);

			// Main menu icon if needed
			$icon = current($GLOBALS["db"]->fetch("SELECT value_string FROM bancho_settings WHERE name = 'menu_icon'"));
			if ($icon != "")
			{
				$output .= "\x4C\x00\x00";
				$output .= pack("L", strlen($icon)+2);
				$output .= binStr($icon);
			}

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

			// Get our ID and username from token
			$userID = getUserIDFromToken($token);
			$username = getUserUsername($userID);

			// Heavy packet check
			$heavy = isHeavy($token);

			// Check if user has sent a message (public is \x01, private is \x19)
			// if so, add it to DB
			if (($data[0][0] == "\x01" || $data[0][0] == "\x19") && $data[0][1] == "\x00" && $data[0][2] == "\x00")
			{
				// Check if this is a private or public message
				$private = $data[0][0] == "\x19" ? true : false;

				// Get message and channel
				$msg = readBinStr($data, 9);
				$to = substr(readBinStr($data, 9+2+strlen($msg)), 0, -4);

				// Check if we are admin (admins can talk in every channel)
				$isAdmin = checkAdmin($username);

				try
				{
					// Message length check
					if (strlen($msg) <= 0)
						throw new Exception("Error while sending your message");

					// Sender silence check
					if (isSilenced($userID))
						throw new Exception("You are silenced. You can't talk until your silence ends.");

					// Moderated check (public chat only)
					if (!$private && getChannelStatus($to) == 2 && !$isAdmin)
						throw new Exception("The channel is in moderated mode. You can't talk while moderated mode is on.");

					// Public write check (public chat only)
					if (!$private && getChannelPublicWrite($to) == 0 && !$isAdmin)
						throw new Exception("You can't talk in this channel");

					// Receiver silence check (private chat only)
					if ($private && isSilenced(getUserOsuID($to)))
						throw new Exception($to." is silenced.");

					// No errors
					// Add our message to DB
					addMessageToDB($userID, $to, $msg, $private);

					// Check if this message has triggered a fokabot command
					fokaBotCommands($username, $to, $msg);

					// Fokabot spam protection
					if (checkSpam($userID))
					{
						silenceUser($userID, time()+300, "Spamming (FokaBot spam protection)");
						kickUser($userID);
						if (!$private)
							addMessageToDB(999, $to, $username." has been silenced (FokaBot spam protection)");
					}
				}
				catch (Exception $e)
				{
					$output .= outputMessage("FokaBot", $username, $e->getMessage());
				}
			}

			// Output unreceived messages if needed
			$messages = getUnreceivedMessages($userID);
			$lastPublic = 0;
			$lastPrivate = 0;
			if ($messages)
			{
				foreach ($messages["public"] as $message) {
					$output .= outputMessage($message["msg_from_username"], $message["msg_to"], $message["msg"]);
					$lastPublic = $message["id"];
				}

				foreach ($messages["private"] as $message) {
					$output .= outputMessage($message["msg_from_username"], $message["msg_to"], $message["msg"]);
					$lastPrivate = $message["id"];
				}
			}

			// If we have received some messages, update our latest message ID
			if ($lastPublic != 0)
				updateLatestMessageID($userID, $lastPublic);

			if ($lastPrivate != 0)
				updateLatestPrivateMessageID($userID, $lastPrivate);


			// Send updated userpanel if we've submitted a score
			// or we have changed our gamemode
			// and set our action to idle
			// (packet starts with \x00\x00\x00\x0E\x00\x00\x00)
			if ($data[0][0] == "\x00" && $data[0][1] == "\x00" && $data[0][2] == "\x00"
			&& $data[0][3] == "\x0E" && $data[0][4] == "\x00" && $data[0][5] == "\x00" && $data[0][6] == "\x00")
			{
				$gameMode = intval(unpack("C",$data[0][16])[1]);
				setGameMode($userID, $gameMode);
				setAction($userID, 0);
				setActionText($userID, "");
				$output .= userPanel($userID);
				$output .= userStats($userID);
			}

			// Output online users (panel only) if needed (and if this is not an heavy packet)
			if (($data[0][0] == "\x55" && $data[0][1] == "\x00" && $data[0][2] == "\x00") && !$heavy)
			{
				$output .= outputOnlineUsers(false);
				$output .= outputOfflineUsers(false);
			}

			// Heavy packet, output online users panels, user stats and channels
			if ($heavy)
			{
				// User panels and stats packets
				$output .= outputOnlineUsers(true);

				// Channels info packets
				$channels = $GLOBALS["db"]->fetchAll("SELECT * FROM bancho_channels");
				foreach ($channels as $channel)
					$output .= outputChannel($channel["name"], $channel["description"], 1337);
			}

			// Update our action if needed
			if ($data[0][0] == "\x00" && $data[0][1] == "\x00" && $data[0][2] == "\x00")
			{
				// Get new action
				$action = intval(unpack("C",$data[0][7])[1]);
				setAction($userID, $action);

				// Get new action text if we are playing
				// if we're not playing, reset action text
				if ($action == 2 || $action == 3 || $action == 4 || $action == 6 || $action == 8)
					$actionText = readBinStr($data, 8);
				else
					$actionText = "";

				setActionText($userID, $actionText);

				// Output new action
				$output .= userPanel($userID);
				$output .= userStats($userID);
			}

			// Channel join
			if ($data[0][0] == "\x3F" && $data[0][1] == "\x00" && $data[0][2] == "\x00")
			{
				$channel = readBinStr($data, 7);
				$output .= outputChannelJoin($username, $channel);
			}

			// Channel part
			if ($data[0][0] == "\x4E" && $data[0][1] == "\x00" && $data[0][2] == "\x00")
			{
				$channel = readBinStr($data, 7);
				partChannel($userID, $channel);
			}

			// Add friend
			if ($data[0][0] == "\x49" && $data[0][1] == "\x00" && $data[0][2] == "\x00")
			{
				// Get friend ID
				$uid = intval(unpack("L", $data[0][7].$data[0][8].$data[0][9].$data[0][10])[1]);
				addFriend($userID, $uid, true);
			}

			// Remove friend
			if ($data[0][0] == "\x4A" && $data[0][1] == "\x00" && $data[0][2] == "\x00")
			{
				// Get friend ID
				$uid = intval(unpack("L", $data[0][7].$data[0][8].$data[0][9].$data[0][10])[1]);
				removeFriend($userID, $uid, true);
			}

			// Update latest packet time
			updateLatestPacketTime($userID, time(), $heavy);

			// Output everything
			outGz($output);
		}
	}
?>
