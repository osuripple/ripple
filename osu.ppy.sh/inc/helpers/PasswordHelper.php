<?php

class PasswordHelper {
	public static $dumb_passwords = ['password', '12345678', '123456789', 'iloveyou', 'adobe123', '1234567890', 'photoshop', 'sunshine', 'password1', 'princess', 'trustno1', 'passw0rd', 'princess', '1234567890', 'football', 'jennifer', 'superman'];

	public static function ValidatePassword($pass, $pass2 = null) {
		// Check password length
		if (strlen($pass) < 8) {
			return 1;
		}
		// Check if passwords match
		if ($pass2 !== null && $pass != $pass2) {
			return 2;
		}
		// god damn i hate people
		if (in_array($pass, self::$dumb_passwords)) {
			return 3;
		}

		return -1;
	}

	public static function CheckPass($u, $pass, $is_already_md5 = true) {
		if (empty($u) || empty($pass)) {
			return false;
		}
		if (!$is_already_md5) {
			$pass = md5($pass);
		}
		$uPass = $GLOBALS['db']->fetch('SELECT password_md5, salt, password_version FROM users WHERE username = ?', [$u]);
		// Check it exists
		if ($uPass === false) {
			return false;
		}
		// password version 2: password_hash() + password_verify() + md5()
		if ($uPass['password_version'] == 2) {
			return password_verify($pass, $uPass['password_md5']);
			exit;
		}
		// password_version 1: crypt() + md5()
		if ($uPass['password_version'] == 1) {
			if ($uPass['password_md5'] != (crypt($pass, '$2y$'.base64_decode($uPass['salt'])))) {
				return false;
			}
			// password is good. convert it to new password
			$newPass = password_hash($pass, PASSWORD_DEFAULT);
			$GLOBALS['db']->execute("UPDATE users SET password_md5=?, salt='', password_version='2' WHERE username = ?", [$newPass, $u]);

			return true;
		}
		// whatever
		return true;
	}
}
