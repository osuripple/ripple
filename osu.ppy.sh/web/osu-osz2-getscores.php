<?php
	/*
	 * Top (50) scores.
	 * Still needs some optimization.
	 */
	require_once("../inc/functions.php");

	try
	{
		// Output variables if we want it
		if ($GETSCORES["outputParams"])
			outputVariable("getscores-vars.txt", $_POST);

		// Check if everything is set
		if (!isset($_GET["c"]) || !isset($_GET["m"]) || !isset($_GET["i"]) || !isset($_GET["mods"]) || !isset($_GET["us"]) || !isset($_GET["ha"]) || !isset($_GET["f"])) {
			throw new Exception;
		}

		// Check if the user/password is correct
		if (!checkOsuUser($_GET["us"], $_GET["ha"])) {
			throw new Exception("pass");
		}

		// Check if we are banned
		if (getUserAllowed($_GET["us"]) == 0) {
			throw new Exception("pass");
		}

		// Find beatmap status based on configuration
		$ranked = getBeatmapRankedStatus($_GET["f"], $_GET["c"], $GETSCORES["everythingIsRanked"]);

		// Check if result is not empty
		if ($ranked)
		{
			// Beatmap found in db, set $ranked to ranked value
			$ranked = current($ranked);
			switch($ranked)
			{
				case 0:
				{
					// Not ranked, check if latest pending version or update to latest version
					// Compare md5 in db with client's one
					if (compareBeatmapMd5($_GET["f"], $_GET["c"], $GETSCORES["everythingIsRanked"]))
					{
						// Latest pending version
						printBeatmapHeader(0, $_GET["c"]);
						echo("\r\n");
						printBeatmapSongInfo($_GET["c"]);
						printBeatmapAppreciation();
					}
					else
					{
						// Update to latest version
						printBeatmapHeader(1, $_GET["c"]);
						echo("\r\n");
						printBeatmapSongInfo($_GET["c"]);
						printBeatmapAppreciation();
					}
				}
				break;

				case 1:
				{
					// Ranked, compare md5 in db with client's one
					if (compareBeatmapMd5($_GET["f"], $_GET["c"], $GETSCORES["everythingIsRanked"]))
					{
						// Ranked and right md5, show top 50 ranks
						printBeatmapHeader(2, $_GET["c"]);
						echo("\r\n");
						printBeatmapSongInfo($_GET["c"]);
						printBeatmapAppreciation();
						printBeatmapPlayerScore($_GET["us"], $_GET["c"], $_GET["m"]);

						// Print maintenance or top scores
						if (checkGameMaintenance())
							printBeatmapMaintenance();
						else
							printBeatmapTopScores($_GET["c"], $_GET["m"]);

						// End of scores
						echo("\r\n");

						// If everything is ranked, set beatmap name in beatmaps_names
						// based on file name, so we can do top/latest plays without
						// having to put every single beatmap in db
						if ($GETSCORES["everythingIsRanked"])
						{
							// Add song to beatmaps names table only if has at least 1 score,
							// and it's not in the db yet, because if noone has played this beatmap,
							// it'll never be shown on top/latest rank page
							if ( (count($GLOBALS["db"]->fetchAll("SELECT id FROM scores WHERE beatmap_md5 = ?", $_GET["c"])) > 0) && !($GLOBALS["db"]->fetch("SELECT id FROM beatmaps_names WHERE beatmap_md5 = ?", $_GET["c"])) )
							{
								// We have scores and beatmap name isn't in the db yet, add it
								// (we remove last 4 chars from file name aka .osu)
								$GLOBALS["db"]->execute("INSERT INTO beatmaps_names (`id`, `beatmap_md5`, `beatmap_name`) VALUES (NULL, ?, ?)", array($_GET["c"], substr($_GET["f"], 0, strlen($_GET["f"]) - 4) ));
							}
						}
					}
					else
					{
						// Not submitted
						printBeatmapHeader(-1);
					}
				}
				break;

				default:
				{
					// Some kind of error, not submitted
					printBeatmapHeader(-1);
				}
				break;
			}
		}
		else
		{
			// Beatmap not found in db, not submitted
			printBeatmapHeader(-1);
		}
	}
	catch (Exception $e)
	{
		// Error
		echo("error: ".$e->getMessage());
	}
 ?>