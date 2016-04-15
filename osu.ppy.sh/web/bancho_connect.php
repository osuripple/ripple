<?php
/*
 * Initializes a connection to bancho.
 *
 * GET parameters:
 * v - the current osu! version (e.g. b20150807)
 * u - username
 * h - password hash
*/
require_once dirname(__FILE__).'/../inc/functions.php';
$d = $GLOBALS['db']->fetch('SELECT country FROM users_stats WHERE username = ?', [$_GET['u']]);
if ($d !== false && $d['country'] !== 'XX') {
	echo strtolower($d['country']);
} else {
	echo 'us';
}
