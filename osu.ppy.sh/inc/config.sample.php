<?php

	/*
	 * Database configuration.
	 */
	define('DATABASE_NAME', 'ripple');
	define('DATABASE_USER', 'root');
	define('DATABASE_PASS', 'password');
	define('DATABASE_HOST', 'ripple');
	// Either host or unix_socket.
	// This is really important. On most installations, it will just work
	// by doing DATABASE_HOST as localhost and DATABASE_WHAT as "host".
	// That's the recommended setting if you do not know what you're doing.
	// However, if you need extra speed in making queries and you know where your
	// mysql.sock is located, you can change the DATABASE_HOST to the Location
	// of the mysql.sock file, and DATABASE_WHAT to "unix_socket".
	// Using the UNIX socket, the latency between the database and the application
	// will be really much closer to 0 than using classic connections to
	// 127.0.0.1.
	define('DATABASE_WHAT', "host");

	/*
	 * osu-submit-modular.php configuration
	 */
	$SUBMIT["AESKey"] 				= 'h89f2-890h2h89b34g-h80g134n90133';	// AES Encryption key for decrypt score data. Don't touch.
	$SUBMIT["outputParams"] 		= false;								// If true, outputs $_POST params to a txt file. Only for debugging purposes.
	$SUBMIT["saveFailedScores"] 	= false;								// If true, failed/retried scores will be saved in database too (but not shown in leaderboard). Might cause some issues, leave to false.
	$SUBMIT["okOutput"] 			= "ok";									// Output when a score is submitted successfully. Change with pass/beatmap to get a notification in osu! to make sure that this script works fine

	/*
	 * osu-osz2-getscores.php configuration
	 */
	$GETSCORES["everythingIsRanked"] = true;								// False: Default, get ranked maps from db; True: All beatmaps are ranked
	$GETSCORES["outputParams"] 		 = false;								// If true, outputs $_GET params to a txt file. Only for debugging purposes.

	/*
	 * cron.php configuration
	 */
	$CRON["showSapi"] = false;												// If true, cron.php will show php_sapi_name, so you can set $CRON["sapi"] to the correct value
	$CRON["sapi"] = "cli";													// php_sapi_name() required to run cron.php. Set to "cli" if cron.php is run from command line
	$CRON["adminExec"] = false;												// If true, "Run cron.php" button will run cron.php from command line with exec. If false, the button will run cron.php from browser. Set to false if you are on a windows server

	/*
	 * Changelog configuration
	 */
	$ChangelogConfig = array(
		// If in the commit message any of these words appear, don't show the commit in the changelog.
		"forbidden_keywords" => array("[HIDE]", "[SECRET]"),
		// Labels (keyword,label text,color)
		"labels" => array("[FIX],fix,danger", "[WEB],web,info", "[BANCHO],bancho,warning", "[SCORES],scores,primary", "[NEW],new,success"),
		// If you want to change names from what they appear in the git logs, you can set here a different name for you and your project contributors.
		"change_name" => array("fuck" => "a donkey", "suck" => "a duck")
	);

	/*
	 * Mailgun configuration. Sign up for a free account on mailgun.net and fill
	 * this array. We are not using classical mail() because mails are more
	 * likely to end up in the spam folder.
	 */
	$MailgunConfig = array(
		"domain" => "",
		"key" => ""
	);

?>
