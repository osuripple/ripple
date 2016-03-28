<?php

require_once PATH . "../inc/config.php";

return array(
	'default' => 'mysql',
	'prefix' => '{{prefix}}',
	'connections' => array(
		'mysql' => array(
			'driver' => 'mysql',
			'hostname' => DATABASE_HOST,
			'port' => 3306,
			'username' => DATABASE_USER,
			'password' => DATABASE_PASS,
			'database' => DATABASE_NAME,
			'charset' => 'utf8'
		)
	)
);
