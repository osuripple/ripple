<?php
class Leaderboard {
	const PageID = 13;
	const URL = "leaderboard";
	const Title = "Ripple - Leaderboard";
	const LoggedIn = true;

	public function P() {
		// Leaderboard names (to bold the selected mode)
		$modesText = array(
			0 => "osu!standard",
			1 => "Taiko",
			2 => "Catch the Beat",
			3 => "osu!mania"
		);

		// Set $m value to 0 if not set
		if (!isset($_GET["m"]) || empty($_GET["m"]))
		$m = 0;
		else
		$m = $_GET["m"];

		// Get stats for selected mode
		$modeForDB = getPlaymodeText($m);
		$modeReadable = getPlaymodeText($m, true);
		// Make sure that $m is a valid mode integer
		$m = ($m < 0 || $m > 3 ? 0 : $m);

		// Bold the selected mode
		$modesText[$m] = "<b>" . $modesText[$m] . "</b>";

		// Header stuff
		echo('<blockquote><p>Plz enjoy game.</p><footer>rrtyui</footer></blockquote>');
		echo('<a href="index.php?p=13&m=0">'.$modesText[0].'</a> | <a href="index.php?p=13&m=1">'.$modesText[1].'</a> | <a href="index.php?p=13&m=2">'.$modesText[2].'</a> | <a href="index.php?p=13&m=3">'.$modesText[3].'</a>');

		// Leaderboard
		echo('<table class="table table-striped table-hover">
		<thead>
		<tr>
		<th>Rank</th>
		<th>Player</th>
		<th>Accuracy</th>
		<th>Playcount</th>
		<th>Score</th>
		</tr>
		</thead>');
		echo('<tbody>');

		// Get all user data and order them by score
		$tb = "leaderboard_" . getPlaymodeText($m);
		$leaderboard = $GLOBALS["db"]->fetchAll("SELECT users_stats.*, $tb.* FROM users_stats INNER JOIN $tb ON users_stats.id=$tb.user ORDER BY $tb.position;");

		// Set rank to 0
		$r = 0;

		$allowedUsers = getAllowedUsers();

		// Print table rows
		foreach ($leaderboard as $lbUser)
		{
			// Make sure that this user has a valid osu! (2 is default for not set) id and he's not banned
			if ($lbUser["osu_id"] != "2" && $allowedUsers[$lbUser["username"]])
			{
				// Increment rank
				$r++;

				// Style for top and noob players
				if ($r <= 3)
				{
					// Yellow bg and trophy for top 3 players
					$tc = "warning";
					$rankSymbol = '<i class="fa fa-trophy"></i> ';
				}
				else
				{
					// Standard table style for everyone else
					$tc = "default";
					$rankSymbol = '#';
				}

				// Draw table row for this user
				echo('<tr class="' . $tc . '">
				<td><b>' . $rankSymbol . $r . '</b></td>
				<td><a href="index.php?u=' . $lbUser["osu_id"] . '&m='.$m.'">' . $lbUser["username"] . '</a></td>
				<td>' . (is_numeric($lbUser["avg_accuracy_" . $modeForDB]) ? round($lbUser["avg_accuracy_" . $modeForDB], 2) : "0.00") . '%</td>
				<td>' . number_format($lbUser["playcount_" . $modeForDB]) . '<i> (lvl.'.$lbUser["level_" . $modeForDB].')</i></td>
				<td>' . number_format($lbUser["ranked_score_" . $modeForDB]) . '</td>
				</tr>');
			}
		}

		// Close table
		echo('</tbody></table>');
	}


	static function GetUserRank($u, $mode) {
		$query = $GLOBALS["db"]->fetch("SELECT position FROM leaderboard_$mode WHERE user = ?;", array($u));

		if ($query !== FALSE) {
			$rank = (string)current($query);
		} else {
			$rank = "Unknown";
		}

		return $rank;
	}

	static function BuildLeaderboard() {
		// Declare stuff that will be used later on.
		$modes = array("std", "taiko", "ctb", "mania");
		$data = array(
			"std" => array(),
			"taiko" => array(),
			"ctb" => array(),
			"mania" => array(),
		);

		$allowedUsers = getAllowedUsers("id");

		// Get all user's stats
		$users = $GLOBALS["db"]->fetchAll("SELECT id, ranked_score_std, ranked_score_taiko, ranked_score_ctb, ranked_score_mania FROM users_stats");

		// Put the data in the correct way into the array.
		foreach ($users as $user) {
			if (!$allowedUsers[$user["id"]]) {
				continue;
			}
			foreach ($modes as $mode) {
				$data[$mode][] = array(
					"user" => $user["id"],
					"score" => $user["ranked_score_" . $mode],
				);
			}
		}

		// We're doing the sorting for every mode.
		foreach ($modes as $mode) {
			// Do the sorting
			usort($data[$mode], function($a, $b) {
				if ($a["score"] == $b["score"]) {
					return 0;
				}
				// We're doing ? 1 : -1 because we're doing in descending order.
				return ($a["score"] < $b["score"]) ? 1 : -1;
			});
			// Remove all data from the table
			$GLOBALS["db"]->execute("TRUNCATE TABLE leaderboard_$mode;");
			// And insert each user.
			foreach ($data[$mode] as $key => $val) {
				$GLOBALS["db"]->execute("INSERT INTO leaderboard_$mode (position, user, v) VALUES (?, ?, ?)", array($key+1, $val["user"], $val["score"]));
			}
		}
	}
	static function Update($userID, $newScore, $mode) {
		// Who are we?
		$us = $GLOBALS["db"]->fetch("SELECT * FROM leaderboard_$mode WHERE user=?", array($userID));
		$newplayer = false;
		if (!$us) {
			$newplayer = true;
		}

		// Find player who is right below our score
		$target = $GLOBALS["db"]->fetch("SELECT * FROM leaderboard_$mode WHERE v <= ? ORDER BY position ASC LIMIT 1", array($newScore));
		$plus = 0;
		if (!$target) {
			// Wow, this user completely sucks at this game.
			$target = $GLOBALS["db"]->fetch("SELECT * FROM leaderboard_$mode ORDER BY position DESC LIMIT 1");
			$plus = 1;
		}

		// Set $newT
		if (!$target) {
			// Okay, nevermind. It's not this user to suck. It's just that no-one has ever entered the leaderboard thus far.
			// So, the player is now #1. Yay!
			$newT = 1;
		} else {
			// Otherwise, just give them the position of the target.
			$newT = $target["position"] + $plus;
		}

		// Make some place for the new "place holder".
		if ($newplayer) {
			$GLOBALS["db"]->execute("UPDATE leaderboard_$mode SET position = position + 1 WHERE position >= ? ORDER BY position DESC", array($newT));
		} else {
			$GLOBALS["db"]->execute("DELETE FROM leaderboard_$mode WHERE user = ?", array($userID));
			$GLOBALS["db"]->execute("UPDATE leaderboard_$mode SET position = position + 1 WHERE position < ? AND position >= ? ORDER BY position DESC", array($us["position"], $newT));
		}

		// Finally, insert the user back.
		$GLOBALS["db"]->execute("INSERT INTO leaderboard_$mode (position, user, v) VALUES (?, ?, ?);", array($newT, $userID, $newScore));
	}
}
