<?php
class UsernameHelper {
	// Forbidden usernames array
	// Lowercase only
	static $forbiddenUsernames = array(
		"peppy",
		"rrtyui",
		"cookiezi",
		"azer",
		"loctav",
		"banchobot",
		"happystick",
		"doomsday",
		"sharingan33",
		"andrea",
		"cptnxn",
		"- hakurei reimu-",
		"hvick225",
		"_index",
		"kynan",
		"rafis",
		"sayonara-bye",
		"thelewa",
		"wubwoofwolf",
		"millhioref",
		"tom94",
		"tillerino"
	);

	static function isUsernameForbidden($username) {
		return in_array(strtolower($username), self::$forbiddenUsernames);
	}
}
?>
