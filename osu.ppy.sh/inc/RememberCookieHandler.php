<?php
/**
 * RememberCookieHandler
 * A simple way to remember an user over time.
 *
 * @author kwisk <kwisk@airmail.cc>
 *
 * @version 1.0
 */
class RememberCookieHandler {
	private $ID;

	/**
	 * Check
	 * Checks the user cookie if they have got valid cookies for auto-login.
	 *
	 * @return bool true if the cookies are valid, false otherwise.
	 */
	public function Check() {
		if (!empty($_COOKIE['s']) && !empty($_COOKIE['t']) && is_numeric($_COOKIE['s']) && is_numeric($_COOKIE['t'])) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Validate
	 * Checks if a remember cookie is ok, and logs in the user if it is.
	 *
	 * @return int -1 in case something critical happened (an unauthorized user tried to access the user's account), 0 in case it's a normal failure, 1 if the user is now logged in inside their account. -2 if everything went smooth, but the user is banned.
	 */
	public function Validate() {
		$d = $GLOBALS['db']->fetch('SELECT * FROM remember WHERE series_identifier = ?;', $_COOKIE['s']);
		if ($d === false) {
			// There is no series_identifier for that $_COOKIE["s"], despite being given to the server on the request. Delete the cookies.
			$this->UnsetCookies();

			return 0;
		}
		$this->ID = $d['userid'];
		if (hash('sha256', $_COOKIE['t']) != $d['token_sha']) {
			// Alarm. Thief detected.
			$this->SecureFromThieves();

			return -1;
		} else {
			// Login the user.
			$this->Login();
			$this->UpdateExisting($_COOKIE['s']);

			return 1;
		}
	}

	/**
	 * IssueNew
	 * Issue new permanent cookie for auto-login.
	 */
	public function IssueNew($u) {
		// 2147483647 is int max
		$randmax = (mt_getrandmax() <= 2147483647 ? mt_getrandmax() : 2147483647);
		$sid = mt_rand(0, $randmax);
		$t = mt_rand(0, $randmax);
		setcookie('s', $sid, time() + 60 * 60 * 24 * 30 * 6, '/'); // Six months.
		setcookie('t', $t, time() + 60 * 60 * 24 * 30 * 6, '/');
		$GLOBALS['db']->execute('INSERT INTO remember(userid, series_identifier, token_sha) VALUES (?, ?, ?);', [getUserID($u), $sid, hash('sha256', $t)]);
	}

	/**
	 * Destroy
	 * Destroys a particular sid and token in the database.
	 *
	 * @param string $sid The sid to destroy.
	 */
	public function Destroy($sid) {
		$GLOBALS['db']->execute('DELETE FROM remember WHERE series_identifier = ?', $sid);
	}

	/**
	 * DestroyAll
	 * Destroys all sids and token for the user in the database.
	 *
	 * @param string $u The username.
	 */
	public function DestroyAll($u, $isAlreadyID = false) {
		$GLOBALS['db']->execute('DELETE FROM remember WHERE userid = ?', ($isAlreadyID ? $u : getUserID($u)));
	}

	/**
	 * SecureFromThieves
	 * Deletes all authentication hashes in the database. The user's account is being thieved.
	 * This function also sends an email to the user, telling them about what happened and not to worry if they can't autologin next time.
	 */
	private function SecureFromThieves() {
		$this->DestroyAll($this->ID, true);
		// tell the user they fucked up.
		redirect('index.php?p=2&e=5');
	}

	/**
	 * Login
	 * Login into user's account, onto successful validation.
	 */
	private function Login() {
		// ban check
		if (current($GLOBALS['db']->fetch('SELECT allowed FROM users WHERE id = ?', $this->ID)) === '0') {
			$this->UnsetCookies();
			redirect('index.php?p=2&e=2');
		}
		$password = $GLOBALS['db']->fetch('SELECT password_md5 FROM users WHERE id = ?', $this->ID);
		startSessionIfNotStarted();
		$_SESSION['username'] = getUserUsername($this->ID);
		$_SESSION['password'] = $password['password_md5'];
		$_SESSION['passwordChanged'] = false;
		// Save latest activity
		updateLatestActivity($_SESSION['username']);
	}

	/**
	 * UnsetCookies
	 * Unset the t and s cookies in the user's browser.
	 */
	public function UnsetCookies() {
		unset($_COOKIE['s']);
		setcookie('s', '', time() - 3600, '/');
		unset($_COOKIE['t']);
		setcookie('t', '', time() - 3600, '/');
	}

	/**
	 * UpdateExisting
	 * Updates the existing cookie and the value in the database with a new token.
	 *
	 * @param int $sid series identifier to be updated in the database.
	 */
	private function UpdateExisting($sid) {
		$randmax = (mt_getrandmax() <= 2147483647 ? mt_getrandmax() : 2147483647);
		$t = mt_rand(0, $randmax);
		setcookie('t', $t, time() + 60 * 60 * 24 * 30 * 6, '/'); // Six months.
		$GLOBALS['db']->execute('UPDATE remember SET token_sha = ? WHERE series_identifier = ?;', [hash('sha256', $t), $sid]);
	}
}
