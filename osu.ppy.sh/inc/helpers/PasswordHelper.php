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
                "dragon",
                "passw0rd",
                "flower",
                "121212",
                "hottie",
                "welcome",
                "login",
                "solo",
                "princess",
                "1234567890",
                "football",
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

		$uPass = $GLOBALS["db"]->fetch("SELECT password_md5, salt, password_version FROM users WHERE username = ?", array($u));

		// Check it exists
		if ($uPass === FALSE) {
			return false;
		}

		// password version 2: password_hash() + password_verify() + md5()
		if ($uPass["password_version"] == 2){
			return password_verify($pass, $uPass["password_md5"]);
			exit;
		}

		// password_version 1: crypt() + md5()
		if ($uPass["password_version"] == 1) {
			if ($uPass["password_md5"] != (crypt($pass, "$2y$" . base64_decode($uPass["salt"])))) {
				return false;
			}
			
			// password is good. convert it to new password
			$newPass = password_hash($pass, PASSWORD_DEFAULT);
			$GLOBALS["db"]->execute("UPDATE users SET password_md5=?, salt='', password_version='2' WHERE username = ?", array($newPass, $u));
			return true;
		}
		
		// whatever
		return true;
	}
}
