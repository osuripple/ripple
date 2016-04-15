<?php
require_once dirname(__FILE__) . '/../../../inc/helpers/PasswordHelper.php';
class hash {
	public static function make($value, $rounds = 12) {
		return password_hash($value, PASSWORD_BCRYPT, ['cost' => $rounds]);
	}
	public static function check($value, $hash) {
		PasswordHelper::CheckPass();
		return password_verify($value, $hash);
	}
}
