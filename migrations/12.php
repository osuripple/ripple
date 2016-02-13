<?php
$GLOBALS["db"]->execute("CREATE TABLE leaderboard_std (
	position INT UNSIGNED NOT NULL,
	user INT NOT NULL,
	v BIGINT NOT NULL,
	PRIMARY KEY (position)
)");
$GLOBALS["db"]->execute("CREATE TABLE leaderboard_taiko (
	position INT UNSIGNED NOT NULL,
	user INT NOT NULL,
	v BIGINT NOT NULL,
	PRIMARY KEY (position)
)");
$GLOBALS["db"]->execute("CREATE TABLE leaderboard_ctb (
	position INT UNSIGNED NOT NULL,
	user INT NOT NULL,
	v BIGINT NOT NULL,
	PRIMARY KEY (position)
)");
$GLOBALS["db"]->execute("CREATE TABLE leaderboard_mania (
	position INT UNSIGNED NOT NULL,
	user INT NOT NULL,
	v BIGINT NOT NULL,
	PRIMARY KEY (position)
)");
