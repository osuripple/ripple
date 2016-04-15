<?php

class auth
{
    private static $session = 'auth';

    public static function guest()
    {
        return Session::get(static ::$session) === null;
    }

    public static function user()
    {
        if ($id = Session::get(static ::$session)) {
            return User::find($id);
        }
    }

    public static function admin()
    {
        if ($id = Session::get(static ::$session)) {
            return User::find($id)->rank == 4;
        }

        return false;
    }

    public static function me($id)
    {
        return $id == Session::get(static ::$session);
    }

    public static function attempt($username, $password)
    {
        if ($user = User::where('username', '=', $username)->where('rank', '>', '2')->fetch()) {
            // found a valid user now check the password
			if (password_verify(md5($password), $user->password_md5)) {
			    // store user ID in the session
				Session::put(static ::$session, $user->id);

			    return true;
			}
        }

        return false;
    }

    public static function logout()
    {
        Session::erase(static ::$session);
    }
}
