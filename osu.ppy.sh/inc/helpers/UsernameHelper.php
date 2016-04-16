<?php

class UsernameHelper {
	// Forbidden usernames array
	// Lowercase only
	public static $forbiddenUsernames = ['peppy', 'rrtyui', 'cookiezi', 'azer', 'loctav', 'banchobot', 'happystick', 'doomsday', 'sharingan33', 'andrea', 'cptnxn', 'reimu-desu', 'hvick225', '_index', 'my aim sucks', 'kynan', 'rafis', 'sayonara-bye', 'thelewa', 'wubwoofwolf', 'millhioref', 'tom94', 'tillerino', 'clsw', 'spectator', 'exgon', 'axarious', 'angelsim', 'recia', 'nara', 'emperorpenguin83', 'bikko', 'xilver', 'vettel', 'kuu01', '_yu68', 'tasuke912', 'dusk', 'ttobas', 'velperk', 'jakads', 'jhlee0133', 'abcdullah', 'yuko-', 'entozer', 'hdhr', 'ekoro', 'snowwhite', 'osuplayer111', 'musty', 'nero', 'elysion', 'ztrot', 'koreapenguin', 'fort', 'asphyxia', 'niko'];

	public static function isUsernameForbidden($username) {
		return in_array(strtolower($username), self::$forbiddenUsernames);
	}
}
