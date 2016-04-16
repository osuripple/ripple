<?php
/*
 * Screenshot upload
*/
require_once '../inc/functions.php';
try {
	// Check if everything is set
	if (!isset($_GET['u']) || !isset($_GET['p']) || !($_FILES) || empty($_GET['u']) || empty($_GET['p'])) {
		throw new Exception(1);
	}
	// Check if image file is a actual image or fake image
	if (!getimagesize($_FILES['ss']['tmp_name'])) {
		throw new Exception(2);
	}
	// Check file size
	if ($_FILES['ss']['size'] > 5000000) {
		throw new Exception(3);
	}
	// Check if the user/password is correct
	if (!PasswordHelper::CheckPass($_GET['u'], $_GET['p'])) {
		throw new Exception(4);
	}
	// Try to generate a valid screenshot ID
	$valid = false;
	while (!$valid) {
		$screenshotID = randomString(8);
		if (!file_exists('../ss/'.$screenshotID.'.jpg')) {
			$valid = true;
		}
	}
	// Upload screenshot
	move_uploaded_file($_FILES['ss']['tmp_name'], '../ss/'.$screenshotID.'.jpg');
	// Echo URL
	echo 'https://'.URL::Server().'/ss/'.$screenshotID.'.jpg';
}
catch(Exception $e) {
	// Error, redirect to exception page
	echo 'index.php?s='.$e->getMessage();
}
