<?php
	/*
	 * Cache total scores and total hits in users_stats table,
	 * so we don't have to calculate them manually in the
	 * userpage. Keep in mind that submit-modular.php increments
	 * the value of total score and total hits automatically,
	 * this script should be called every 30 minutes or so
	 * to make sure that the total scores and total hits
	 * are not fucked up (for example, if we remove a score)
	 * from the db, total score won't update until we call
	 * this script to cache total score again
	 */
	require_once("inc/functions.php");

	if ($CRON["showSapi"])
	{
		// Show sapi type and die, so user can set $CRON["sapi"] to the right value
		echo("<font color='red'>Current php_sapi_name: <b>".php_sapi_name()."</b><br>\n");
		echo("<u>Please set <b>\$CRON['sapi']</b> to the right value and set <b>\$CRON['showSapi']</b> to false to run the actual cron.php</b></font><br>\n</u>");
		die();
	}

	if (php_sapi_name() == $CRON["sapi"])
	{
		// If we run this from browser, check if we are admin
		if ($CRON["sapi"] != "cli")
		{
			startSessionIfNotStarted();

			if (!checkLoggedIn())
				die();

			if (getUserRank($_SESSION["username"]) < 4)
			{
				echo("<font color='red'><b>Insufficient permissions</b><br>\n");

				if (isset($_SERVER['HTTP_REFERER']))
					echo("<a href='".$_SERVER['HTTP_REFERER']."'>Go back</a></font>");

				die();
			}
		}

		// Script execution time stuff
		$startTime = microtime(true);

		// Start debug
		echo("<h2>Ripple cron.php</h2>");
		echo("<a href='".(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER['HTTP_REFERER'] : "#")."'>Go back</a><br>\n<br>\n");
		echo("<b>Ripple cron.php started</b><br>\n<br>\n");

		// Delete all password recovery older than 10 days.
		echo('Pruning password recovery submissions older than 10 days...<br>\n');
		$GLOBALS["db"]->execute("DELETE FROM password_recovery WHERE t < (NOW() - INTERVAL 10 DAY);");
		
		// recalculate the accuracy of every score
		echo("Recalculating accuracy.");
		$scores = $GLOBALS["db"]->fetchAll("SELECT * FROM scores");
		foreach ($scores as $score) {
			$acc = calculateAccuracy($score["300_count"], $score["100_count"], $score["50_count"], $score["gekis_count"], $score["katus_count"], $score["misses_count"], $score["play_mode"]);
			$GLOBALS["db"]->execute("UPDATE scores SET accuracy = ? WHERE id = ?", array($acc, $score["id"]));
			echo ".";
		}
		echo " done.\n";

		// Get all users
		$users = $GLOBALS["db"]->fetchAll("SELECT username FROM users WHERE allowed = 1");

		for ($i=0; $i < count($users); $i++)
		{
			// Do this for every ripple user
			// Get current username
			$user = current($users[$i]);

			// Start score caching
			echo("Caching total score for <b>".$user."</b> ...<br>\n");

			// Get all top scores data for this player for every mode
			$scoresStd 		= $GLOBALS["db"]->fetchAll("SELECT * FROM scores WHERE username = ? AND play_mode = 0 AND completed = 3", $user);
			$scoresTaiko 	= $GLOBALS["db"]->fetchAll("SELECT * FROM scores WHERE username = ? AND play_mode = 1 AND completed = 3", $user);
			$scoresCtb 		= $GLOBALS["db"]->fetchAll("SELECT * FROM scores WHERE username = ? AND play_mode = 2 AND completed = 3", $user);
			$scoresMania 	= $GLOBALS["db"]->fetchAll("SELECT * FROM scores WHERE username = ? AND play_mode = 3 AND completed = 3", $user);

			// Sum all the scores for every mode
			$rankedScoreStd = sumScores($scoresStd);
			$rankedScoreTaiko = sumScores($scoresTaiko);
			$rankedScoreCtb = sumScores($scoresCtb);
			$rankedScoreMania = sumScores($scoresMania);

			// Update value in db for every mode
			$GLOBALS["db"]->execute("UPDATE users_stats SET ranked_score_std = ? WHERE username = ?", array($rankedScoreStd, $user));
			$GLOBALS["db"]->execute("UPDATE users_stats SET ranked_score_taiko = ? WHERE username = ?", array($rankedScoreTaiko, $user));
			$GLOBALS["db"]->execute("UPDATE users_stats SET ranked_score_ctb = ? WHERE username = ?", array($rankedScoreCtb, $user));
			$GLOBALS["db"]->execute("UPDATE users_stats SET ranked_score_mania = ? WHERE username = ?", array($rankedScoreMania, $user));

			// Score caching done
			echo("<b>".$user."</b>'s scores cached!<br>\n");


			// Start total hits caching
			echo("Caching total hits for <b>".$user."</b> ...<br>\n");

			// Set total hits to 0
			$totalHitsStd = 0;
			$totalHitsTaiko = 0;
			$totalHitsCtb = 0;
			$totalHitsMania = 0;

			// Get all score data (not only top scores) for this player for every mode
			$scoraDataStd 			= $GLOBALS["db"]->fetchAll("SELECT * FROM scores WHERE username = ? AND play_mode = 0", $user);
			$scoraDataTaiko 		= $GLOBALS["db"]->fetchAll("SELECT * FROM scores WHERE username = ? AND play_mode = 1", $user);
			$scoraDataCtb 			= $GLOBALS["db"]->fetchAll("SELECT * FROM scores WHERE username = ? AND play_mode = 2", $user);
			$scoraDataMania 		= $GLOBALS["db"]->fetchAll("SELECT * FROM scores WHERE username = ? AND play_mode = 3", $user);

			// Sum all hits to get total hits
			$totalHitsStd = sumHits($scoraDataStd);
			$totalHitsTaiko = sumHits($scoraDataTaiko);
			$totalHitsCtb = sumHits($scoraDataCtb);
			$totalHitsMania = sumHits($scoraDataMania);

			// Update total hits value in db for every mode
			$GLOBALS["db"]->execute("UPDATE users_stats SET total_hits_std = ? WHERE username = ?", array($totalHitsStd, $user));
			$GLOBALS["db"]->execute("UPDATE users_stats SET total_hits_taiko = ? WHERE username = ?", array($totalHitsTaiko, $user));
			$GLOBALS["db"]->execute("UPDATE users_stats SET total_hits_ctb = ? WHERE username = ?", array($totalHitsCtb, $user));
			$GLOBALS["db"]->execute("UPDATE users_stats SET total_hits_mania = ? WHERE username = ?", array($totalHitsMania, $user));

			// Total hits caching done
			echo("<b>".$user."</b>'s total hits cached!<br>\n");


			// Start level caching
			echo("Caching level for <b>".$user."</b> ...<br>\n");

			// Get total score of user in each playmode
			$totalScore = $GLOBALS["db"]->fetch("SELECT total_score_std, total_score_taiko, total_score_ctb, total_score_mania FROM users_stats WHERE username = ?", $user);

			// Get level for every mode
			$levelStd = getLevel($totalScore["total_score_std"]);
			$levelTaiko = getLevel($totalScore["total_score_taiko"]);
			$levelCtb = getLevel($totalScore["total_score_ctb"]);
			$levelMania = getLevel($totalScore["total_score_mania"]);

			// Update level value in db for every mode
			$GLOBALS["db"]->execute("UPDATE users_stats SET level_std = ? WHERE username = ?", array($levelStd, $user));
			$GLOBALS["db"]->execute("UPDATE users_stats SET level_taiko = ? WHERE username = ?", array($levelTaiko, $user));
			$GLOBALS["db"]->execute("UPDATE users_stats SET level_ctb = ? WHERE username = ?", array($levelCtb, $user));
			$GLOBALS["db"]->execute("UPDATE users_stats SET level_mania = ? WHERE username = ?", array($levelMania, $user));

			// Level caching done
			echo("<b>".$user."</b>'s level cached!<br>\n");

			echo("Updating average accuracy for <b>".$user."</b> ...<br>\n");
			updateAccuracy($user, 0);
			updateAccuracy($user, 1);
			updateAccuracy($user, 2);
			updateAccuracy($user, 3);
			echo("<b>".$user."</b>'s average accuracy cached!<br>\n");
		}

		// Clear completed 0/1/2 replays
		echo("<br>\n<b>Cleaning replays...</b><br>\n");

		// Get all completed 0/1/2 scores
		$notopScores = $GLOBALS["db"]->fetchAll("SELECT id FROM scores WHERE completed != 3");

		for ($i=0; $i < count($notopScores); $i++) {
			// Check if this useless replay exists and delete it
			$f = "./replays/replay_".$notopScores[$i]["id"].".osr";
			if (file_exists($f))
			{
				unlink($f);
				echo("<b>".$f."</b> deleted!<br>\n");
			}
		}


		// Replays cleared
		echo("<b>Replays cleaned!</b><br>\n");

		// Clear full replays cache
		echo("<br>\n<b>Deleting full replays cache...</b><br>\n");

		$files = scandir("./replays_full");
		foreach ($files as $file)
		{
			if ($file != "." && $file != "..") {				
				unlink("./replays_full/".$file);
				echo("<b>".$file."</b> deleted!<br>\n");
			}
		}


		// Replays cleared
		echo("<b>Full replays cache cleaned!</b><br>\n");
		

		// Delete inactive accounts (not activated in osu!/no osu! id within 30 minutes after registration)
		echo("<br>\n<b>Deleting inactive accounts...</b><br>\n");
		$n = 0;

		// Get all inactive/with no osu id accounts
		$garbage = $GLOBALS["db"]->fetchAll("SELECT * FROM users WHERE osu_id = 2 AND allowed = 2");

		for ($i=0; $i < count($garbage); $i++) {
			// Get 30 mins after the registration of this account
			$registerDate = $garbage[$i]["register_datetime"];
			$expirationDate = $registerDate+1800;

			// Check if this account is expired
			if (time(true) >= $expirationDate)
			{
				// Account expired, delete it
				$GLOBALS["db"]->execute("DELETE FROM users WHERE id = ?", $garbage[$i]["id"]);
				echo("Deleted <b>".$garbage[$i]["username"]."</b>'s account<br>\n");
				$n++;
			}
		}

		echo("<b>Deleted ".$n." inactive accounts!</b><br>\n");

		echo("<br>\n<b>Building leaderboard...</b></br>\n");
		Leaderboard::BuildLeaderboard();
		echo("<br>\n<b>Leaderboard built!</b></br>\n");

		// Get execution time
		$endTime = microtime(true);
		$executionTime = ($endTime - $startTime);
		echo('<br>
<b>Ripple cron.php stopped</b><br>
<b>Total execution time:</b> '.$executionTime.' seconds<br>');

		// Get max execution time
		$maxTime = ini_get('max_execution_time');
		echo("\n<b>Maximum execution time:</b> ".$maxTime." seconds<br>\n");

		// Check if script is safe to run
		if ($maxTime == 0 || $maxTime-$executionTime > 5)
			$safe = true;
		else
			$safe = false;

		// Output safe
		if ($safe)
			echo("<br>\n<b><font color='green'>Execution time OK!</font></b>");
		else
			echo("<br>\n<b><font color='red'>The script takes much time to run. Please consider incrementing your max_execution_time in php.ini</font></b>");
	}
?>
