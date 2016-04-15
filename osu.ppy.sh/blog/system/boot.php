<?php
/**
 * Nano.
 *
 * Just another php framework
 *
 * @link		http://madebykieron.co.uk
 *
 * @copyright	http://unlicense.org/
 */
/**
 * Check php version.
 */
if (version_compare(PHP_VERSION, '5.3') < 0) {
	echo 'We need PHP 5.3 or higher, you are running '.PHP_VERSION;
	exit;
}
/*
 * Register Globals Fix
*/
if (ini_get('register_globals')) {
	$sg = [$_REQUEST, $_SERVER, $_FILES];
	if (isset($_SESSION)) {
		array_unshift($sg, $_SESSION);
	}
	foreach ($sg as $global) {
		foreach (array_keys($global) as $key) {
			unset($ {
				$key
			});
		}
	}
}
/*
 * Magic Quotes Fix
*/
if (get_magic_quotes_gpc()) {
	$gpc = [&$_GET, &$_POST, &$_COOKIE, &$_REQUEST];
	array_walk_recursive($gpc, function (&$value) {
		$value = stripslashes($value);
	});
}
/**
 * Include base classes and functions.
 */
require PATH.'system/helpers'.EXT;
require PATH.'system/error'.EXT;
require PATH.'system/arr'.EXT;
require PATH.'system/config'.EXT;
require PATH.'system/autoloader'.EXT;
/*
 * Register the autoloader
*/
spl_autoload_register(['System\\Autoloader', 'load']);
// set the base path to search
System\Autoloader::directory(PATH);
// map application aliases to autoloader so we dont
// have to fully specify the class namespaces each time.
System\Autoloader::$aliases = (array) System\Config::aliases();
/*
 * Error handling
*/
set_exception_handler(['System\\Error', 'exception']);
set_error_handler(['System\\Error', 'native']);
register_shutdown_function(['System\\Error', 'shutdown']);
