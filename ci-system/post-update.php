<?php
include dirname(__FILE__) . "/../osu.ppy.sh/inc/functions.php";
$users = $GLOBALS["db"]->fetchAll("SELECT id, password_md5, salt FROM users");
foreach ($users as $user) {
	echo "updating $user[id]...\n";
	$pass = crypt($user["password_md5"], "$2y$" . base64_decode($user["salt"]));
	$GLOBALS["db"]->execute("UPDATE users SET password_md5 = ? WHERE id = ?", array($pass, $user["id"]));
}
