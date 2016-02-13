<?php
class Leaderboard {
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
