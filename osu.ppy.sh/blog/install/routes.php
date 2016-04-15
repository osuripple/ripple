<?php

require_once PATH . '../inc/functions.php';
/*
    Filters
*/
Route::action('check', function () {
});
/*
    Start (Language Select)
*/
Route::get(['/', 'start'], ['before' => 'check', 'main' => function () {
	$vars['messages'] = Notify::read();
	$vars['languages'] = languages();
	$vars['prefered_languages'] = prefered_languages();
	$vars['timezones'] = timezones();
	$vars['current_timezone'] = current_timezone();

	return Layout::create('start', $vars);
},
]);
Route::post('start', ['before' => 'check', 'main' => function () {
	$i18n = Input::get(['language', 'timezone']);
	$validator = new Validator($i18n);
	$validator->check('language')->is_max(2, 'Please select a language');
	$validator->check('timezone')->is_max(2, 'Please select a timezone');
	if ($errors = $validator->errors()) {
		Input::flash();
		Notify::error($errors);

		return Response::redirect('start');
	}
	Session::put('install.i18n', $i18n);

	return Response::redirect('database');
},
]);
/*
    MySQL Database
*/
Route::get('database', ['before' => 'check', 'main' => function () {
	// check we have a selected language
	if (!Session::get('install.i18n')) {
		Notify::error('Please select a language');

		return Response::redirect('start');
	}
	$vars['messages'] = Notify::read();
	$vars['collations'] = ['utf8_bin' => 'Unicode (multilingual), Binary', 'utf8_czech_ci' => 'Czech, case-insensitive', 'utf8_danish_ci' => 'Danish, case-insensitive', 'utf8_esperanto_ci' => 'Esperanto, case-insensitive', 'utf8_estonian_ci' => 'Estonian, case-insensitive', 'utf8_general_ci' => 'Unicode (multilingual), case-insensitive', 'utf8_hungarian_ci' => 'Hungarian, case-insensitive', 'utf8_icelandic_ci' => 'Icelandic, case-insensitive', 'utf8_latvian_ci' => 'Latvian, case-insensitive', 'utf8_lithuanian_ci' => 'Lithuanian, case-insensitive', 'utf8_persian_ci' => 'Persian, case-insensitive', 'utf8_polish_ci' => 'Polish, case-insensitive', 'utf8_roman_ci' => 'West European, case-insensitive', 'utf8_romanian_ci' => 'Romanian, case-insensitive', 'utf8_slovak_ci' => 'Slovak, case-insensitive', 'utf8_slovenian_ci' => 'Slovenian, case-insensitive', 'utf8_spanish2_ci' => 'Traditional Spanish, case-insensitive', 'utf8_spanish_ci' => 'Spanish, case-insensitive', 'utf8_swedish_ci' => 'Swedish, case-insensitive', 'utf8_turkish_ci' => 'Turkish, case-insensitive', 'utf8_unicode_ci' => 'Unicode (multilingual), case-insensitive'];

	return Layout::create('database', $vars);
},
]);
Route::post('database', ['before' => 'check', 'main' => function () {
	$database = Input::get(['collation', 'prefix']);
	// Escape the password input
	$database['pass'] = addslashes(DATABASE_PASS);
	// test connection
	try {
		$connection = DB::factory(['driver' => 'mysql', 'database' => DATABASE_NAME, 'hostname' => DATABASE_HOST, 'port' => 3306, 'username' => DATABASE_USER, 'password' => DATABASE_PASS, 'charset' => 'utf8', 'prefix' => $database['prefix']]);
	}
	catch(PDOException $e) {
		Input::flash();
		Notify::error($e->getMessage());

		return Response::redirect('database');
	}
	Session::put('install.database', $database);

	return Response::redirect('metadata');
},
]);
/*
    Metadata
*/
Route::get('metadata', ['before' => 'check', 'main' => function () {
	// check we have a database
	if (!Session::get('install.database')) {
		Notify::error('Please enter your database details');

		return Response::redirect('database');
	}
	$vars['messages'] = Notify::read();
	$vars['site_path'] = dirname(dirname($_SERVER['SCRIPT_NAME']));
	$vars['themes'] = Themes::all();
	//  Fix for Windows screwing up directories
	$vars['site_path'] = str_replace('\\', '/', $vars['site_path']);

	return Layout::create('metadata', $vars);
},
]);
Route::post('metadata', ['before' => 'check', 'main' => function () {
	$metadata = Input::get(['site_name', 'site_description', 'site_path', 'theme', 'rewrite']);
	$validator = new Validator($metadata);
	$validator->check('site_name')->is_max(4, 'Please enter a site name');
	$validator->check('site_description')->is_max(4, 'Please enter a site description');
	$validator->check('site_path')->is_max(1, 'Please enter a site path');
	$validator->check('theme')->is_max(1, 'Please select a site theme');
	if ($errors = $validator->errors()) {
		Input::flash();
		Notify::error($errors);

		return Response::redirect('metadata');
	}
	Session::put('install.metadata', $metadata);

	return Response::redirect('account');
},
]);
/*
    Account
*/
Route::get('account', ['before' => 'check', 'main' => function () {
	// check we have a database
	if (!Session::get('install.metadata')) {
		Notify::error('Please enter your site details');

		return Response::redirect('metadata');
	}
	$vars['messages'] = Notify::read();

	return Layout::create('account', $vars);
},
]);
Route::post('account', ['before' => 'check', 'main' => function () {
	$account = Input::get(['username', 'password']);
	$validator = new Validator($account);
	$validator->check('username')->is_max(3, 'Please enter a username');
	$uPass = $GLOBALS['db']->fetch('SELECT password_md5, salt, rank FROM users WHERE username = ?', [$account['username']]);
	// Check it exists
	if ($uPass === false) {
		Input::flash();
		Notify::error('Invalid username.');

		return Response::redirect('account');
	}
	// Check the md5 password is valid
	if ($uPass['password_md5'] != (crypt(md5($account['password']), '$2y$' . base64_decode($uPass['salt'])))) {
		Input::flash();
		Notify::error('Invalid password.');

		return Response::redirect('account');
	}
	if ($uPass['rank'] != 4) {
		Input::flash();
		Notify::error("Don't you dare ye cunt. (not an admin)");

		return Response::redirect('account');
	}
	if ($errors = $validator->errors()) {
		Input::flash();
		Notify::error($errors);

		return Response::redirect('account');
	}
	Session::put('install.account', $account);
	// run install process
	try {
		Installer::run();
	}
	catch(Exception $e) {
		Input::flash();
		Notify::error($e->getMessage());

		return Response::redirect('account');
	}

	return Response::redirect('complete');
},
]);
/*
    Complete
*/
Route::get('complete', function () {
	// check we have a database
	if (!Session::get('install')) {
		Notify::error('Please select your language');

		return Response::redirect('start');
	}
	$settings = Session::get('install');
	$vars['site_uri'] = $settings['metadata']['site_path'];
	$vars['admin_uri'] = rtrim($settings['metadata']['site_path'], '/') . '/admin/login';
	$vars['htaccess'] = Session::get('htaccess');
	// scrub session now we are done
	Session::erase('install');

	return Layout::create('complete', $vars);
});
/*
    404 catch all
*/
Route::any(':all', function () {
	return Response::error(404);
});
