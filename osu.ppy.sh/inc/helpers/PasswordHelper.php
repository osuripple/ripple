<?php
class PasswordHelper {
	static $dumb_passwords = array(
		"123456",
		"password",
		"12345678",
		"qwerty",
		"abc123",
		"123456789",
		"111111",
		"1234567",
		"iloveyou",
		"adobe123",
		"123123",
		"admin",
		"1234567890",
		"letmein",
		"photoshop",
		"1234",
		"monkey",
		"shadow",
		"sunshine",
		"12345",
		"password1",
		"princess",
		"azerty",
		"trustno1",
		"000000",
	);
	static function ValidatePassword($pass, $pass2 = null) {
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
	static function CheckPass($u, $pass, $is_already_md5 = true) {
		if (empty($u) || empty($pass)) {
			return false;
		}
		if (!$is_already_md5) {
			$pass = md5($pass);
		}

		$uPass = $GLOBALS["db"]->fetch("SELECT password_md5, salt FROM users WHERE username = ?", array($u));

		// Check it exists
		if ($uPass === FALSE) {
			return false;
		}

		// Check the md5 password is valid
		if ($uPass["password_md5"] != (crypt($pass, "$2y$" . base64_decode($uPass["salt"])))) {
			return false;
		}

		// Everything ok, return true
		return true;
	}
}
