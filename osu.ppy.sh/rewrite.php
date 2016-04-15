<?php

// Require functions file
require_once './inc/functions.php';
// URI and explode
$uri = $_SERVER['REQUEST_URI'];
$uri = explode('/', $uri);
// Redirect to the right url with right parameter
switch ($uri[1]) {
		// Redirect to userpage

	case 'u':
		redirect('../index.php?u='.$uri[2]);
	break;
		// Redirect to bloodcat map download

	case 'd':
		redirect(file_get_contents('http://bcache.zxq.co/download.php?s='.explode('?', $uri[2]) [0]));
	break;
		// No matches, redirect to index

	default:
		redirect('../index.php');
	break;
}
