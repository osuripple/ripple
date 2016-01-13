<?php
	// Require functions file
	require_once("./inc/functions.php");

	// URI and explode
	$uri = $_SERVER["REQUEST_URI"];
	$uri = explode("/",$uri);

	// Redirect to the right url with right parameter
	switch($uri[1])
	{
		case 'u': redirect("../index.php?u=".$uri[2]); break;
		default: redirect("index.php"); break;
	}
?>
