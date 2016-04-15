<?php
/*
 * Replay downloading.
*/
require_once '../inc/functions.php';
try {
	// Check if everything is set
	if (!isset($_GET['c']) || !isset($_GET['u']) || !isset($_GET['h']) || empty($_GET['c']) || empty($_GET['u']) || empty($_GET['h'])) {
		throw new Exception();
	}
	// Check login
	if (!PasswordHelper::CheckPass($_GET['u'], $_GET['h'])) {
		throw new Exception();
	}
	// Check ban
	if (getUserAllowed($_GET['u']) == 0) {
		throw new Exception();
	}
	// Get replay content
	$replayData = file_get_contents('../replays/replay_'.$_GET['c'].'.osr');
	// Check replay
	if ($replayData) {
		// Replay exists, check if we want to watch someone else's replay
		// If so, update replays watched by others for that user
		// Remove last space from $whois, because osu has memes and sends
		// username with a space at the end when submitting scores
		$whois = rtrim(current($GLOBALS['db']->fetch('SELECT username FROM scores WHERE id = ?', [$_GET['c']])), ' ');
		if ($whois != $_GET['u']) {
			//$mode = current($GLOBALS["db"]->fetch("SELECT play_mode FROM scores WHERE id = ?", array($_GET["c"])));
			$mode = $_GET['m'];
			switch ($mode) {
				case 0:
					$modeForDB = 'std';
				break;
				case 1:
					$modeForDB = 'taiko';
				break;
				case 2:
					$modeForDB = 'ctb';
				break;
				case 3:
					$modeForDB = 'mania';
				break;
			}
			$GLOBALS['db']->execute('UPDATE users_stats SET replays_watched_'.$modeForDB.'=replays_watched_'.$modeForDB.'+1 WHERE username = ?', [$whois]);
		}
		// Output replay content
		echo $replayData;
	} else {
		// Replay doesn't exists, output nothing
		throw new Exception();
	}
}
catch(Exception $e) {
	// Error

}
