<?php
// Crop all avatars to 100x100 resolution
include dirname(__FILE__) . "/inc/functions.php";
$users = $GLOBALS["db"]->fetchAll("SELECT id FROM users");
foreach ($users as $user) {
	$avatar = dirname(__FILE__) . "/../a.ppy.sh/avatars/".$user["id"].".png";
	if (file_exists($avatar))
		smart_resize_image($avatar, null, 100, 100, false , $avatar, false, false, 100);
}
