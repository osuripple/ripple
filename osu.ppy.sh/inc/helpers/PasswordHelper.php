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
}
