<?php

Route::collection(['before' => 'auth,csrf,install_exists'], function () {
	/*
				    List users
	*/
	Route::get(['admin/users', 'admin/users/(:num)'], function ($page = 1) {
		$vars['messages'] = Notify::read();
		$vars['users'] = User::paginate($page, Config::get('admin.posts_per_page'));

		return View::create('users/index', $vars)->partial('header', 'partials/header')->partial('footer', 'partials/footer');
	});
	/*
				    Edit user
	*/
	Route::get('admin/users/edit/(:num)', function ($id) {
		return View::create('users/edit', [])->partial('header', 'partials/header')->partial('footer', 'partials/footer');
	});
	Route::post('admin/users/edit/(:num)', function ($id) {
		return Response::redirect('admin/users/edit/' . $id);
	});
	/*
				    Add user
	*/
	Route::get('admin/users/add', function () {
		return View::create('users/add', $vars)->partial('header', 'partials/header')->partial('footer', 'partials/footer');
	});
	Route::post('admin/users/add', function () {
		return Response::redirect('admin/users');
	});
	/*
				    Delete user
	*/
	Route::get('admin/users/delete/(:num)', function ($id) {
		$self = Auth::user();
		if ($self->id == $id) {
			Notify::error(__('users.delete_error'));

			return Response::redirect('admin/users/edit/' . $id);
		}
		User::where('id', '=', $id)->delete();
		Query::table(Base::table('user_meta'))->where('user', '=', $id)->delete();
		Notify::success(__('users.deleted'));

		return Response::redirect('admin/users');
	});
});
