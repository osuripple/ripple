<?php
	/*
	 * Replay downloading.
	 */
	require_once("../inc/functions.php");

	try
	{
		// Check if everything is set
		if (!isset($_GET["c"]) || !isset($_GET["u"]) || !isset($_GET["h"]) || empty($_GET["c"]) || empty($_GET["u"]) || empty($_GET["h"]) ) {
			throw new Exception;
		}

		// Check login
		if (!checkOsuUser($_GET["u"], $_GET["h"])) {
			throw new Exception;
		}

		// Check ban
		if (getUserAllowed($_GET["u"]) == 0) {
			throw new Exception;			
		}

		// Get replay content
		$replayData = file_get_contents("../replays/replay_".$_GET["c"].".osr");

		// Check replay
		if ($replayData)
		{
			// Replay exists, output content
			echo($replayData);
		}
		else
		{
			// Replay doesn't exists, output nothing
			throw new Exception;
		}
	}
	catch (Exception $e)
	{
		// Error
	}
?>