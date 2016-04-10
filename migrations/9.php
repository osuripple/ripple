<?php

echo "Fixing remember tokens...\n";

$remUsers = $GLOBALS["db"]->fetchAll("SELECT * FROM remember");

$new = array();

foreach ($remUsers as $k => $v) {
	$new[] = array(
		"id" => getUserID($v["username"]),
		"series_identifier" => $v["series_identifier"],
		"token_sha" => $v["token_sha"],
	);
}

$GLOBALS["db"]->execute("TRUNCATE TABLE remember");
$GLOBALS["db"]->execute("ALTER TABLE remember CHANGE username userid INT(11) NOT NULL, ADD COLUMN id INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY(id);");

foreach ($new as $u) {
	print("Updating " . $u["id"] . "...");
	$GLOBALS["db"]->execute("INSERT INTO remember(userid, series_identifier, token_sha) VALUES (?, ?, ?)", $u["id"], $u["series_identifier"], $u["token_sha"]);
}
