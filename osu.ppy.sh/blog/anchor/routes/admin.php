<?php
/**
 * Admin actions.
 */
Route::action('auth', function () {
	if (Auth::guest()) {
		return Response::redirect('admin/login');
	}
});
Route::action('guest', function () {
	if (Auth::user()) {
		return Response::redirect('admin/post');
	}
});
Route::action('csrf', function () {
	if (Request::method() == 'POST') {
		if (!Csrf::check(Input::get('token'))) {
			Notify::error(['Invalid token']);

			return Response::redirect('admin/login');
		}
	}
});
Route::action('install_exists', function () {
});
/*
 * Admin routing
*/
Route::get('admin', function () {
	if (Auth::guest()) {
		return Response::redirect('admin/login');
	}

	return Response::redirect('admin/panel');
});
/*
    Log in
*/
// Why check if we haven't deleted the install directory, BEFORE we've logged in? Isn't that just unlocking the door for the burglars to enter?
//Route::get('admin/login', array('before' => 'install_exists', 'main' => function() {
Route::get('admin/login', ['before' => 'guest', 'main' => function () {
	if (!Auth::guest()) {
		return Response::redirect('admin/posts');
	}
	$vars['messages'] = Notify::read();
	$vars['token'] = Csrf::token();

	return View::create('users/login', $vars)->partial('header', 'partials/header')->partial('footer', 'partials/footer');
},
]);
Route::post('admin/login', ['before' => 'csrf', 'main' => function () {
	$attempt = Auth::attempt(Input::get('user'), Input::get('pass'));
	if (!$attempt) {
		Notify::error(__('users.login_error'));

		return Response::redirect('admin/login');
	}
	// check for updates
	Update::version();
	if (version_compare(Config::get('meta.update_version'), VERSION, '>')) {
		return Response::redirect('admin/upgrade');
	}

	return Response::redirect('admin/panel');
},
]);
/*
    Log out
*/
Route::get('admin/logout', function () {
	Auth::logout();
	Notify::notice(__('users.logout_notice'));

	return Response::redirect('admin/login');
});
/*
    Amnesia
*/
Route::get('admin/amnesia', ['before' => 'guest', 'main' => function () {
	// Dirty hack to send to the ripple reset password page.
	return Response::redirect('/../index.php?p=18');
},
]);
Route::post('admin/amnesia', ['before' => 'csrf', 'main' => function () {
	// Dirty hack to send to the ripple reset password page.
	return Response::redirect('/../index.php?p=18');
},
]);
/*
    Reset password
*/
Route::get('admin/reset/(:any)', ['before' => 'guest', 'main' => function ($key) {
	return View::create('users/reset', [])->partial('header', 'partials/header')->partial('footer', 'partials/footer');
},
]);
Route::post('admin/reset/(:any)', ['before' => 'csrf', 'main' => function ($key) {
	return Response::redirect('admin/login');
},
]);
/*
    Upgrade
*/
Route::get('admin/upgrade', function () {
	return View::create('upgrade', [])->partial('header', 'partials/header')->partial('footer', 'partials/footer');
});
/*
    List extend
*/
Route::get('admin/extend', ['before' => 'auth', 'main' => function ($page = 1) {
	$vars['messages'] = Notify::read();
	$vars['token'] = Csrf::token();

	return View::create('extend/index', $vars)->partial('header', 'partials/header')->partial('footer', 'partials/footer');
},
]);
Route::post('admin/get_fields', ['before' => 'auth', 'main' => function () {
	$input = Input::get(['id', 'pagetype']);
	// get the extended fields
	$vars['fields'] = Extend::fields('page', -1, $input['pagetype']);
	$html = View::create('pages/fields', $vars)->render();
	$token = '<input name="token" type="hidden" value="' . Csrf::token() . '">';

	return Response::json(['token' => $token, 'html' => $html]);
},
]);
/*
    Upload an image
*/
Route::post('admin/upload', ['before' => 'auth', 'main' => function () {
	$uploader = new Uploader(PATH . 'content', ['png', 'jpg', 'bmp', 'gif', 'pdf']);
	$filepath = $uploader->upload($_FILES['file']);
	$uri = Config::app('url', '/') . 'content/' . basename($filepath);
	$output = ['uri' => $uri];

	return Response::json($output);
},
]);
/*
    404 error
*/
Route::error('404', function () {
	return Response::error(404);
});
