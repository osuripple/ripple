<?php
require_once(dirname(__FILE__) . "/../osu.ppy.sh/inc/functions.php");
$messages = $GLOBALS["db"]->fetchAll("SELECT * FROM bancho_messages");
foreach ($messages as $message)
	$GLOBALS["db"]->execute("UPDATE bancho_messages SET msg_from_username = ? WHERE id = ?", array(getUserUsername($message["msg_from_userid"]), $message["id"]));