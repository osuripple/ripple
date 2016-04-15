<?php
require_once PATH . '../inc/config.php';
class installer {
	// database connection
	public static $connection;
	/*
	       Install
	*/
	public static function run() {
		// session data
		$settings = Session::get('install');
		// create database connection
		static ::connect($settings);
		// install tables
		static ::schema($settings);
		// insert metadata
		static ::metadata($settings);
		// create user account
		static ::account($settings);
		// write database config
		static ::database($settings);
		// write application config
		static ::application($settings);
		// write session config
		static ::session($settings);
		// install htaccess file
		static ::rewrite($settings);
	}
	private static function connect($settings) {
		$database = $settings['database'];
		$config = ['driver' => 'mysql', 'database' => DATABASE_NAME, 'hostname' => DATABASE_HOST, 'port' => 3306, 'username' => DATABASE_USER, 'password' => DATABASE_PASS, 'charset' => 'utf8', ];
		static ::$connection = DB::factory($config);
	}
	private static function schema($settings) {
		$database = $settings['database'];
		$sql = Braces::compile(APP . 'storage/anchor.sql', ['now' => gmdate('Y-m-d H:i:s'), 'charset' => 'utf8', 'prefix' => $database['prefix'], ]);
		static ::$connection->instance()->query($sql);
	}
	private static function metadata($settings) {
		$metadata = $settings['metadata'];
		$database = $settings['database'];
		$config = ['sitename' => $metadata['site_name'], 'description' => $metadata['site_description'], 'theme' => $metadata['theme'], ];
		$query = Query::table($database['prefix'] . 'meta', static ::$connection);
		foreach ($config as $key => $value) {
			$query->insert(['key' => $key, 'value' => $value]);
		}
	}
	private static function account($settings) {
		// Not used because we're using ripple's accounts anyway.
		
	}
	private static function database($settings) {
		$database = $settings['database'];
		$distro = Braces::compile(APP . 'storage/database.distro.php', ['prefix' => $database['prefix'], 'database' => DATABASE_NAME, 'hostname' => DATABASE_HOST, 'port' => 3306, 'username' => DATABASE_USER, 'password' => DATABASE_PASS, ]);
		file_put_contents(PATH . 'anchor/config/db.php', $distro);
	}
	private static function application($settings) {
		$distro = Braces::compile(APP . 'storage/application.distro.php', ['url' => $settings['metadata']['site_path'], 'index' => '', 'key' => noise(), 'language' => $settings['i18n']['language'], 'timezone' => $settings['i18n']['timezone'], ]);
		file_put_contents(PATH . 'anchor/config/app.php', $distro);
	}
	private static function session($settings) {
		$database = $settings['database'];
		$distro = Braces::compile(APP . 'storage/session.distro.php', ['table' => $database['prefix'] . 'sessions', ]);
		file_put_contents(PATH . 'anchor/config/session.php', $distro);
	}
	private static function rewrite($settings) {
		if (mod_rewrite() or (is_apache() and $settings['metadata']['rewrite'])) {
			$htaccess = Braces::compile(APP . 'storage/htaccess.distro', ['base' => $settings['metadata']['site_path'], 'index' => (is_cgi() ? 'index.php?/$1' : 'index.php/$1'), ]);
			if (isset($htaccess) and is_writable($filepath = PATH . '.htaccess')) {
				file_put_contents($filepath, $htaccess);
			} else {
				Session::put('htaccess', $htaccess);
			}
		}
	}
}
