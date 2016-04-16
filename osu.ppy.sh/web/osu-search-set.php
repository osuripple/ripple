<?php
/*
 * This file fixes #ILBUGDELLENNEPPI
 * Thanks to Avail for request/response structure :3
*/
require_once '../inc/functions.php';
try {
	// Check if everything is set
	if (!isset($_GET['u']) || !isset($_GET['h']) || empty($_GET['u']) || empty($_GET['h']) || !isset($_GET['b']) || empty($_GET['b'])) {
		throw new Exception();
	}
	// Check login
	if (!PasswordHelper::CheckPass($_GET['u'], $_GET['h'])) {
		throw new Exception();
	}
	// Get API response and save it in an array
	$bcURL = 'http://bloodcat.com/osu/?mod=json&c=s&q='.$_GET['b'];
	$bcData = json_decode(file_get_contents($bcURL), true);
	// Make sure the beatmap was found
	if (count($bcData) == 0) {
		throw new Exception();
	}
	// Output result
	echo bloodcatDirectString($bcData[0], true);
}
catch(Exception $e) {
	echo $e->getMessage();
}
