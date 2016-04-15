<?php
// We aren't calling the class Do because otherwise it would conflict with do { } while ();
class D {
	/*
	 * Register
	 * Register function
	*/
	public static function Register() {
		try {
			// Check if everything is set
			if (empty($_POST['u']) || empty($_POST['p1']) || empty($_POST['p2']) || empty($_POST['e']) || empty($_POST['k'])) {
				throw new Exception(0);
			}
			// Validate password through our helper
			$pres = PasswordHelper::ValidatePassword($_POST['p1'], $_POST['p2']);
			if ($pres !== - 1) {
				throw new Exception($pres);
			}
			// Check if email is valid
			if (!filter_var($_POST['e'], FILTER_VALIDATE_EMAIL)) {
				throw new Exception(4);
			}
			// Check if username is valid
			if (!preg_match('/^[A-Za-z0-9 _\\-\\[\\]]{3,20}$/i', $_POST['u'])) {
				throw new Exception(5);
			}
			// Make sure username is not forbidden
			if (UsernameHelper::isUsernameForbidden($_POST['u'])) {
				throw new Exception(9);
			}
			// Check if username is already in db
			if ($GLOBALS['db']->fetch('SELECT * FROM users WHERE username = ?', $_POST['u'])) {
				throw new Exception(6);
			}
			// Check if email is already in db
			if ($GLOBALS['db']->fetch('SELECT * FROM users WHERE email = ?', $_POST['e'])) {
				throw new Exception(7);
			}
			// Check if beta key is valid
			if (!$GLOBALS['db']->fetch('SELECT id FROM beta_keys WHERE key_md5 = ? AND allowed = 1', md5($_POST['k']))) {
				throw new Exception(8, 1);
			}
			// Create password
			$md5Password = password_hash(md5($_POST['p1']), PASSWORD_DEFAULT);
			// Put some data into the db
			$GLOBALS['db']->execute("INSERT INTO `users`(username, password_md5, salt, email, register_datetime, rank, allowed, password_version) 
			                                     VALUES (?,        ?,            '',    ?,     ?,                 1,   1,       2);", [$_POST['u'], $md5Password, $_POST['e'], time(true) ]);
			// Get user ID
			$uid = $GLOBALS['db']->lastInsertId();
			// Put some data into users_stats
			$GLOBALS['db']->execute("INSERT INTO `users_stats`(id, username, user_color, user_style, ranked_score_std, playcount_std, total_score_std, ranked_score_taiko, playcount_taiko, total_score_taiko, ranked_score_ctb, playcount_ctb, total_score_ctb, ranked_score_mania, playcount_mania, total_score_mania) VALUES (?, ?, 'black', '', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);", [$uid, $_POST['u']]);
			// Update leaderboard (insert new user) for each mode.
			foreach (['std', 'taiko', 'ctb', 'mania'] as $m) {
				Leaderboard::Update($uid, 0, $m);
			}
			// Invalidate beta key
			$GLOBALS['db']->execute('UPDATE beta_keys SET allowed = 0 WHERE key_md5 = ?', md5($_POST['k']));
			// All fine, done
			redirect('index.php?p=3&s=lmao');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=3&e=' . $e->getMessage());
		}
	}
	/*
	 * ChangePassword
	 * Change password function
	*/
	public static function ChangePassword() {
		try {
			// Check if we are logged in
			sessionCheck();
			// Check if everything is set
			if (empty($_POST['pold']) || empty($_POST['p1']) || empty($_POST['p2'])) {
				throw new Exception(0);
			}
			$pres = PasswordHelper::ValidatePassword($_POST['p1'], $_POST['p2']);
			if ($pres !== - 1) {
				throw new Exception($pres);
			}
			if (!PasswordHelper::CheckPass($_SESSION['username'], $_POST['pold'], false)) {
				throw new Exception(4);
			}
			// Calculate new password
			$newPassword = password_hash(md5($_POST['p1']), PASSWORD_DEFAULT);
			// Change both passwords and salt
			$GLOBALS['db']->execute("UPDATE users SET password_md5 = ?, password_version = 2, salt = '' WHERE username = ?", [$newPassword, $_SESSION['username']]);
			// Set in session that we've changed our password otherwise sessionCheck() will kick us
			$_SESSION['passwordChanged'] = true;
			// Redirect to success page
			redirect('index.php?p=7&s=done');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=7&e=' . $e->getMessage());
		}
	}
	/*
	 * RecoverPassword()
	 * Form submission for printPasswordRecovery.
	*/
	public static function RecoverPassword() {
		global $MailgunConfig;
		try {
			if (!isset($_POST['username']) || empty($_POST['username'])) {
				throw new Exception(0);
			}
			$username = $_POST['username'];
			$user = $GLOBALS['db']->fetch('SELECT username, email, allowed FROM users WHERE username = ?', [$username]);
			// Check the user actually exists.
			if (!$user) {
				throw new Exception(1);
			}
			if ($user['allowed'] == '0') {
				throw new Exception(2);
			}
			$key = randomString(80);
			$GLOBALS['db']->execute('INSERT INTO password_recovery (k, u) VALUES (?, ?);', [$key, $username]);
			require_once dirname(__FILE__) . '/SimpleMailgun.php';
			$mailer = new SimpleMailgun($MailgunConfig);
			$mailer->Send('Ripple <noreply@' . $MailgunConfig['domain'] . '>', $user['email'], 'Ripple password recovery instructions', sprintf("Hey %s! Someone, which we really hope was you, requested a password reset for your account. In case it was you, please <a href='%s'>click here</a> to reset your password on Ripple. Otherwise, silently ignore this email.", $username, 'http://' . $_SERVER['HTTP_HOST'] . '/index.php?p=19&k=' . $key . '&user=' . $username));
			redirect('index.php?p=18&s=sent');
		}
		catch(Exception $e) {
			redirect('index.php?p=18&e=' . $e->getMessage());
		}
	}
	/*
	 * GenerateBetaKey
	 * Generate beta key(s) function
	*/
	public static function GenerateBetaKey() {
		try {
			// Check if everything is set
			if (empty($_POST['n'])) {
				throw new Exception('Nice troll.');
			}
			// Set public value
			$p = isset($_POST['p']) ? 1 : 0;
			if ($p == 1) {
				$desc = '*key*';
			} else {
				$desc = $_POST['d'];
			}
			// We store plain keys here to show them at the end
			$plainKeys = '';
			// Generate all the keys
			for ($i = 0;$i < $_POST['n'];$i++) {
				$d = false;
				while ($d == false) {
					$key = generateKey();
					$hash = md5($key);
					if (!$GLOBALS['db']->fetch('SELECT * FROM beta_keys WHERE key_md5 = ?', $hash)) {
						$GLOBALS['db']->execute('INSERT INTO beta_keys(key_md5, description, allowed, public) VALUES (?, ?, ?, ?);', [$hash, str_replace('*key*', $key, $desc), 1, $p]);
						$d = true;
						$plainKeys = $plainKeys . '<br>' . $key;
					} else {
						$d = false;
					}
				}
			}
			// Beta keys generated, go to done page
			redirect('index.php?p=105&s=<b>Beta keys generated!</b>' . $plainKeys);
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=105&e=' . $e->getMessage());
		}
	}
	/*
	 * AllowDisallowBetaKey
	 * Allow/Disallow beta key function (ADMIN CP)
	*/
	public static function AllowDisallowBetaKey() {
		try {
			// Check if everything is set
			if (empty($_GET['id'])) {
				throw new Exception('Nice troll.');
			}
			// Get current allowed value of this beta key
			$allowed = current($GLOBALS['db']->fetch('SELECT allowed FROM beta_keys WHERE id = ?', $_GET['id']));
			// Get new allowed value
			if ($allowed == 1) {
				$newAllowed = 0;
			} else {
				$newAllowed = 1;
			}
			// Change allowed value
			$GLOBALS['db']->execute('UPDATE beta_keys SET allowed = ? WHERE id = ?', [$newAllowed, $_GET['id']]);
			// Done, redirect to success page
			redirect('index.php?p=105&s=Allowed value changed!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=105&e=' . $e->getMessage());
		}
	}
	/*
	 * PublicPrivateBetaKey
	 * Public/private beta key function (ADMIN CP)
	*/
	public static function PublicPrivateBetaKey() {
		try {
			// Check if everything is set
			if (empty($_GET['id'])) {
				throw new Exception('Nice troll.');
			}
			// Get current public value of this beta key
			$public = current($GLOBALS['db']->fetch('SELECT public FROM beta_keys WHERE id = ?', $_GET['id']));
			// Get new public value
			if ($public == 1) {
				$newPublic = 0;
			} else {
				$newPublic = 1;
			}
			// Change allowed value
			$GLOBALS['db']->execute('UPDATE beta_keys SET public = ? WHERE id = ?', [$newPublic, $_GET['id']]);
			// Done, redirect to success page
			redirect('index.php?p=105&s=Public value changed!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=105&e=' . $e->getMessage());
		}
	}
	/*
	 * RemoveBetaKey
	 * Remove beta key function (ADMIN CP)
	*/
	public static function RemoveBetaKey() {
		try {
			// Check if everything is set
			if (empty($_GET['id'])) {
				throw new Exception('Nice troll.');
			}
			// Make sure that this key exists
			$exists = $GLOBALS['db']->fetch('SELECT * FROM beta_keys WHERE id = ?', $_GET['id']);
			// Beta key doesn't exists wtf
			if (!$exists) {
				throw new Exception("This beta key doesn\'t exists");
			}
			// Delete beta key
			$GLOBALS['db']->execute('DELETE FROM beta_keys WHERE id = ?', $_GET['id']);
			// Done, redirect to success page
			redirect('index.php?p=105&s=Beta key deleted!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=105&e=' . $e->getMessage());
		}
	}
	/*
	 * SaveSystemSettings
	 * Save system settings function (ADMIN CP)
	*/
	public static function SaveSystemSettings() {
		try {
			// Get values
			if (isset($_POST['wm'])) {
				$wm = $_POST['wm'];
			} else {
				$wm = 0;
			}
			if (isset($_POST['gm'])) {
				$gm = $_POST['gm'];
			} else {
				$gm = 0;
			}
			if (isset($_POST['r'])) {
				$r = $_POST['r'];
			} else {
				$r = 0;
			}
			if (!empty($_POST['ga'])) {
				$ga = $_POST['ga'];
			} else {
				$ga = '';
			}
			if (!empty($_POST['ha'])) {
				$ha = $_POST['ha'];
			} else {
				$ha = '';
			}
			// Save new values
			$GLOBALS['db']->execute("UPDATE system_settings SET value_int = ? WHERE name = 'website_maintenance'", [$wm]);
			$GLOBALS['db']->execute("UPDATE system_settings SET value_int = ? WHERE name = 'game_maintenance'", [$gm]);
			$GLOBALS['db']->execute("UPDATE system_settings SET value_int = ? WHERE name = 'registrations_enabled'", [$r]);
			$GLOBALS['db']->execute("UPDATE system_settings SET value_string = ? WHERE name = 'website_global_alert'", [$ga]);
			$GLOBALS['db']->execute("UPDATE system_settings SET value_string = ? WHERE name = 'website_home_alert'", [$ha]);
			// Done, redirect to success page
			redirect('index.php?p=101&s=Settings saved!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=101&e=' . $e->getMessage());
		}
	}
	/*
	 * SaveBanchoSettings
	 * Save bancho settings function (ADMIN CP)
	*/
	public static function SaveBanchoSettings() {
		try {
			// Get values
			if (isset($_POST['bm'])) {
				$bm = $_POST['bm'];
			} else {
				$bm = 0;
			}
			if (isset($_POST['od'])) {
				$od = $_POST['od'];
			} else {
				$od = 0;
			}
			if (isset($_POST['rm'])) {
				$rm = $_POST['rm'];
			} else {
				$rm = 0;
			}
			if (!empty($_POST['mi'])) {
				$mi = $_POST['mi'];
			} else {
				$mi = '';
			}
			if (!empty($_POST['lm'])) {
				$lm = $_POST['lm'];
			} else {
				$lm = '';
			}
			if (!empty($_POST['ln'])) {
				$ln = $_POST['ln'];
			} else {
				$ln = '';
			}
			if (!empty($_POST['cv'])) {
				$cv = $_POST['cv'];
			} else {
				$cv = '';
			}
			if (!empty($_POST['cmd5'])) {
				$cmd5 = $_POST['cmd5'];
			} else {
				$cmd5 = '';
			}
			// Save new values
			$GLOBALS['db']->execute("UPDATE bancho_settings SET value_int = ? WHERE name = 'bancho_maintenance'", [$bm]);
			$GLOBALS['db']->execute("UPDATE bancho_settings SET value_int = ? WHERE name = 'free_direct'", [$od]);
			$GLOBALS['db']->execute("UPDATE bancho_settings SET value_int = ? WHERE name = 'restricted_joke'", [$rm]);
			$GLOBALS['db']->execute("UPDATE bancho_settings SET value_string = ? WHERE name = 'menu_icon'", [$mi]);
			$GLOBALS['db']->execute("UPDATE bancho_settings SET value_string = ? WHERE name = 'login_messages'", [$lm]);
			$GLOBALS['db']->execute("UPDATE bancho_settings SET value_string = ? WHERE name = 'login_notification'", [$ln]);
			$GLOBALS['db']->execute("UPDATE bancho_settings SET value_string = ? WHERE name = 'osu_versions'", [$cv]);
			$GLOBALS['db']->execute("UPDATE bancho_settings SET value_string = ? WHERE name = 'osu_md5s'", [$cmd5]);
			// Done, redirect to success page
			redirect('index.php?p=111&s=Settings saved!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=111&e=' . $e->getMessage());
		}
	}
	/*
	 * RunCron
	 * Runs cron.php from admin cp with exec/redirect
	*/
	public static function RunCron() {
		if ($CRON['adminExec']) {
			// howl master linux shell pr0
			exec(PHP_BIN_DIR . '/php ' . dirname(__FILE__) . '/../cron.php 2>&1 > /dev/null &');
		} else {
			// Run from browser
			redirect('./cron.php');
		}
	}
	/*
	 * SaveEditUser
	 * Save edit user function (ADMIN CP)
	*/
	public static function SaveEditUser() {
		try {
			// Check if everything is set (username color, username style, rank and allowed can be empty)
			if (!isset($_POST['id']) || !isset($_POST['u']) || !isset($_POST['e']) || !isset($_POST['up']) || !isset($_POST['aka']) || !isset($_POST['se']) || !isset($_POST['sr']) || empty($_POST['id']) || empty($_POST['u']) || empty($_POST['e'])) {
				throw new Exception('Nice troll');
			}
			// Check if this user exists
			$id = current($GLOBALS['db']->fetch('SELECT id FROM users WHERE id = ?', $_POST['id']));
			if (!$id) {
				throw new Exception("That user doesn\'t exists");
			}
			// Check if we can edit this user
			if (getUserRank($_POST['u']) >= getUserRank($_SESSION['username']) && $_POST['u'] != $_SESSION['username']) {
				throw new Exception("You dont't have enough permissions to edit this user");
			}
			// Check if email is valid
			if (!filter_var($_POST['e'], FILTER_VALIDATE_EMAIL)) {
				throw new Exception("The email isn't valid");
			}
			// Check if silence end has changed. if so, we have to kick the client
			// in order to silence him
			//$oldse = current($GLOBALS["db"]->fetch("SELECT silence_end FROM users WHERE username = ?", array($_POST["u"])));
			// Save new data (email, silence end and silence reason)
			$GLOBALS['db']->execute('UPDATE users SET email = ?, silence_end = ?, silence_reason = ? WHERE id = ?', [$_POST['e'], $_POST['se'], $_POST['sr'], $_POST['id']]);
			// Save new userpage
			$GLOBALS['db']->execute('UPDATE users_stats SET userpage_content = ? WHERE id = ?', [$_POST['up'], $_POST['id']]);
			// Save new data if set (rank, allowed, UP and silence)
			if (isset($_POST['r']) && !empty($_POST['r'])) {
				$GLOBALS['db']->execute('UPDATE users SET rank = ? WHERE id = ?', [$_POST['r'], $_POST['id']]);
			}
			if (isset($_POST['a'])) {
				$GLOBALS['db']->execute('UPDATE users SET allowed = ? WHERE id = ?', [$_POST['a'], $_POST['id']]);
			}
			// Get username style/color
			if (isset($_POST['c']) && !empty($_POST['c'])) {
				$c = $_POST['c'];
			} else {
				$c = 'black';
			}
			if (isset($_POST['bg']) && !empty($_POST['bg'])) {
				$bg = $_POST['bg'];
			} else {
				$bg = '';
			}
			// Set username style/color/aka
			$GLOBALS['db']->execute('UPDATE users_stats SET user_color = ?, user_style = ?, username_aka = ? WHERE id = ?', [$c, $bg, $_POST['aka'], $_POST['id']]);
			// Done, redirect to success page
			redirect('index.php?p=102&s=User edited!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=102&e=' . $e->getMessage());
		}
	}
	/*
	 * BanUnbanUser
	 * Ban/Unban user function (ADMIN CP)
	*/
	public static function BanUnbanUser() {
		try {
			// Check if everything is set
			if (empty($_GET['id'])) {
				throw new Exception('Nice troll.');
			}
			// Get username
			$username = current($GLOBALS['db']->fetch('SELECT username FROM users WHERE id = ?', $_GET['id']));
			// Check if we can ban this user
			if (getUserRank($username) >= getUserRank($_SESSION['username'])) {
				throw new Exception("You dont't have enough permissions to ban this user");
			}
			// Get current allowed value of this user
			$allowed = current($GLOBALS['db']->fetch('SELECT allowed FROM users WHERE id = ?', $_GET['id']));
			// Get new allowed value
			if ($allowed == 1) {
				$newAllowed = 0;
			} else {
				$newAllowed = 1;
			}
			// Change allowed value
			$GLOBALS['db']->execute('UPDATE users SET allowed = ? WHERE id = ?', [$newAllowed, $_GET['id']]);
			// Done, redirect to success page
			redirect('index.php?p=102&s=User banned/unbanned/activated!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=102&e=' . $e->getMessage());
		}
	}
	/*
	 * QuickEditUser
	 * Redirects to the edit user page for the user with $_POST["u"] username
	*/
	public static function QuickEditUser() {
		try {
			// Check if everything is set
			if (empty($_POST['u'])) {
				throw new Exception('Nice troll.');
			}
			// Get user id
			$id = current($GLOBALS['db']->fetch('SELECT id FROM users WHERE username = ?', $_POST['u']));
			// Check if that user exists
			if (!$id) {
				throw new Exception("That user doesn't exists");
			}
			// Done, redirect to edit page
			redirect('index.php?p=103&id=' . $id);
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=102&e=' . $e->getMessage());
		}
	}
	/*
	 * QuickEditUserBadges
	 * Redirects to the edit user badges page for the user with $_POST["u"] username
	*/
	public static function QuickEditUserBadges() {
		try {
			// Check if everything is set
			if (empty($_POST['u'])) {
				throw new Exception('Nice troll.');
			}
			// Get user id
			$id = current($GLOBALS['db']->fetch('SELECT id FROM users WHERE username = ?', $_POST['u']));
			// Check if that user exists
			if (!$id) {
				throw new Exception("That user doesn't exists");
			}
			// Done, redirect to edit page
			redirect('index.php?p=110&id=' . $id);
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=108&e=' . $e->getMessage());
		}
	}
	/*
	 * ChangeIdentity
	 * Change identity function (ADMIN CP)
	*/
	public static function ChangeIdentity() {
		try {
			// Check if everything is set
			if (!isset($_POST['id']) || !isset($_POST['oldu']) || !isset($_POST['newu']) || !isset($_POST['ks']) || empty($_POST['id']) || empty($_POST['oldu']) || empty($_POST['newu'])) {
				throw new Exception('Nice troll.');
			}
			// Check if we can edit this user
			if (getUserRank($_POST['oldu']) >= getUserRank($_SESSION['username']) && $_POST['oldu'] != $_SESSION['username']) {
				throw new Exception("You dont't have enough permissions to edit this user");
			}
			// Change stuff
			$GLOBALS['db']->execute('UPDATE users SET username = ? WHERE id = ?', [$_POST['newu'], $_POST['id']]);
			$GLOBALS['db']->execute('UPDATE users_stats SET username = ? WHERE id = ?', [$_POST['newu'], $_POST['id']]);
			// Change username in scores if needed
			if ($_POST['ks'] == 1) {
				$GLOBALS['db']->execute('UPDATE scores SET username = ? WHERE username = ?', [$_POST['newu'], $_POST['oldu']]);
			}
			// Done, redirect to success page
			redirect('index.php?p=102&s=User identity changed!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=102&e=' . $e->getMessage());
		}
	}
	/*
	 * SaveDocFile
	 * Save doc file function (ADMIN CP)
	*/
	public static function SaveDocFile() {
		try {
			// Check if everything is set
			if (!isset($_POST['id']) || !isset($_POST['t']) || !isset($_POST['c']) || !isset($_POST['p']) || empty($_POST['t']) || empty($_POST['c'])) {
				throw new Exception('Nice troll.');
			}
			// Check if we are creating or editing a doc page
			if ($_POST['id'] == 0) {
				$GLOBALS['db']->execute('INSERT INTO docs (id, doc_name, doc_contents, public) VALUES (NULL, ?, ?, ?)', [$_POST['t'], $_POST['c'], $_POST['p']]);
			} else {
				$GLOBALS['db']->execute('UPDATE docs SET doc_name = ?, doc_contents = ?, public = ? WHERE id = ?', [$_POST['t'], $_POST['c'], $_POST['p'], $_POST['id']]);
			}
			// Done, redirect to success page
			redirect('index.php?p=106&s=Documentation page edited!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=106&e=' . $e->getMessage());
		}
	}
	/*
	 * SaveBadge
	 * Save badge function (ADMIN CP)
	*/
	public static function SaveBadge() {
		try {
			// Check if everything is set
			if (!isset($_POST['id']) || !isset($_POST['n']) || !isset($_POST['i']) || empty($_POST['n']) || empty($_POST['i'])) {
				throw new Exception('Nice troll.');
			}
			// Check if we are creating or editing a doc page
			if ($_POST['id'] == 0) {
				$GLOBALS['db']->execute('INSERT INTO badges (id, name, icon) VALUES (NULL, ?, ?)', [$_POST['n'], $_POST['i']]);
			} else {
				$GLOBALS['db']->execute('UPDATE badges SET name = ?, icon = ? WHERE id = ?', [$_POST['n'], $_POST['i'], $_POST['id']]);
			}
			// Done, redirect to success page
			redirect('index.php?p=108&s=Badge edited!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=108&e=' . $e->getMessage());
		}
	}
	/*
	 * SaveUserBadges
	 * Save user badges function (ADMIN CP)
	*/
	public static function SaveUserBadges() {
		try {
			// Check if everything is set
			if (!isset($_POST['u']) || !isset($_POST['b01']) || !isset($_POST['b02']) || !isset($_POST['b03']) || !isset($_POST['b04']) || !isset($_POST['b05']) || !isset($_POST['b06']) || empty($_POST['u'])) {
				throw new Exception('Nice troll.');
			}
			// Make sure that this user exists
			if (!$GLOBALS['db']->fetch('SELECT id FROM users WHERE username = ?', $_POST['u'])) {
				throw new Exception("That user doesn't exists.");
			}
			// Get the string with all the badges
			$badgesString = $_POST['b01'] . ',' . $_POST['b02'] . ',' . $_POST['b03'] . ',' . $_POST['b04'] . ',' . $_POST['b05'] . ',' . $_POST['b06'];
			// Save the new badges string
			$GLOBALS['db']->execute('UPDATE users_stats SET badges_shown = ? WHERE username = ?', [$badgesString, $_POST['u']]);
			// Done, redirect to success page
			redirect('index.php?p=108&s=Badge edited!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=108&e=' . $e->getMessage());
		}
	}
	/*
	 * RemoveDocFile
	 * Delete doc file function (ADMIN CP)
	*/
	public static function RemoveDocFile() {
		try {
			// Check if everything is set
			if (!isset($_GET['id']) || empty($_GET['id'])) {
				throw new Exception('Nice troll.');
			}
			// Check if this doc page exists
			if (!$GLOBALS['db']->fetch('SELECT * FROM docs WHERE id = ?', $_GET['id'])) {
				throw new Exception("That documentation page doesn't exists");
			}
			// Delete doc page
			$GLOBALS['db']->execute('DELETE FROM docs WHERE id = ?', $_GET['id']);
			// Done, redirect to success page
			redirect('index.php?p=106&s=Documentation page deleted!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=106&e=' . $e->getMessage());
		}
	}
	/*
	 * RemoveBadge
	 * Remove badge function (ADMIN CP)
	*/
	public static function RemoveBadge() {
		try {
			// Make sure that this is not the "None badge"
			if (empty($_GET['id'])) {
				throw new Exception("You can't delete this badge.");
			}
			// Make sure that this badge exists
			$exists = $GLOBALS['db']->fetch('SELECT * FROM badges WHERE id = ?', $_GET['id']);
			// Beta key doesn't exists wtf
			if (!$exists) {
				throw new Exception("This badge doesn't exists");
			}
			// Delete badge
			$GLOBALS['db']->execute('DELETE FROM badges WHERE id = ?', $_GET['id']);
			// Done, redirect to success page
			redirect('index.php?p=108&s=Badge deleted!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=108&e=' . $e->getMessage());
		}
	}
	/*
	 * SilenceUser
	 * Silence someone (ADMIN CP)
	*/
	public static function SilenceUser() {
		try {
			// Check if everything is set
			if (!isset($_POST['u']) || !isset($_POST['c']) || !isset($_POST['un']) || !isset($_POST['r']) || empty($_POST['u']) || empty($_POST['c']) || empty($_POST['un']) || empty($_POST['r'])) {
				throw new Exception('Invalid request');
			}
			// Get user id
			$id = current($GLOBALS['db']->fetch('SELECT id FROM users WHERE username = ?', $_POST['u']));
			// Check if that user exists
			if (!$id) {
				throw new Exception("That user doesn't exists");
			}
			// Calculate silence period length
			$sl = $_POST['c'] * $_POST['un'];
			// Make sure silence time is less than 7 days
			if ($sl > 604800) {
				throw new Exception('Invalid silence length. Maximum silence length is 7 days.');
			}
			// Silence and reconnect that user
			silenceUser($id, time() + $sl, $_POST['r']);
			kickUser($id);
			// Done, redirect to success page
			redirect('index.php?p=102&s=User silenced!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=102&e=' . $e->getMessage());
		}
	}
	/*
	 * KickUser
	 * Kick someone from bancho (ADMIN CP)
	*/
	public static function KickUser() {
		try {
			// Check if everything is set
			if (!isset($_POST['u']) || empty($_POST['u'])) {
				throw new Exception('Invalid request');
			}
			// Get user id
			$id = current($GLOBALS['db']->fetch('SELECT id FROM users WHERE username = ?', $_POST['u']));
			// Check if that user exists
			if (!$id) {
				throw new Exception("That user doesn't exists");
			}
			// Kick that user
			kickUser($id);
			// Done, redirect to success page
			redirect('index.php?p=102&s=User kicked!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=102&e=' . $e->getMessage());
		}
	}
	/*
	 * ResetAvatar
	 * Reset soneone's avatar (ADMIN CP)
	*/
	public static function ResetAvatar() {
		try {
			// Check if everything is set
			if (!isset($_GET['id']) || empty($_GET['id'])) {
				throw new Exception('Invalid request');
			}
			// Get user id
			$avatar = dirname(dirname(dirname(__FILE__))) . '/a.ppy.sh/avatars/' . $_GET['id'] . '.png';
			if (!file_exists($avatar)) {
				throw new Exception("That user doesn't have an avatar");
			}
			// Delete user avatar
			unlink($avatar);
			// Done, redirect to success page
			redirect('index.php?p=102&s=Avatar reset!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=102&e=' . $e->getMessage());
		}
	}
	/*
	 * Logout
	 * Logout and return to home
	*/
	public static function Logout() {
		// Logging out without being logged in doesn't make much sense
		if (checkLoggedIn()) {
			startSessionIfNotStarted();
			if (isset($_COOKIE['s']) && isset($_COOKIE['t'])) {
				$rch = new RememberCookieHandler();
				// Desu-troy permanent session.
				$rch->Destroy($_COOKIE['s']);
				$rch->UnsetCookies();
			}
			$_SESSION = [];
			session_destroy();
		} else {
			// Uhm, some kind of error/h4xx0r. Let's return to login page just because yes.
			redirect('index.php?p=2');
		}
	}
	/*
	 * ForgetEveryCookie
	 * Allows the user to delete every field in the remember database table with their username, so that it is logged out of every computer they were logged in.
	*/
	public static function ForgetEveryCookie() {
		startSessionIfNotStarted();
		$rch = new RememberCookieHandler();
		$rch->DestroyAll($_SESSION['username']);
		redirect('index.php?p=1&s=forgetDone');
	}
	/*
	 * saveUserSettings
	 * Save user settings functions
	*/
	public static function saveUserSettings() {
		global $PlayStyleEnum;
		try {
			// Check if we are logged in
			sessionCheck();
			// Check if everything is set
			if (!isset($_POST['f']) || !isset($_POST['c']) || !isset($_POST['aka']) || !isset($_POST['st'])) {
				throw new Exception(0);
			}
			// Check if username color is not empty and if so, set to black (default)
			if (empty($_POST['c'])) {
				$c = 'black';
			} else {
				$c = $_POST['c'];
			}
			// Playmode stuff
			$pm = 0;
			foreach ($_POST as $key => $value) {
				$i = str_replace('_', ' ', substr($key, 3));
				if ($value == 1 && substr($key, 0, 3) == 'ps_' && isset($PlayStyleEnum[$i])) {
					$pm+= $PlayStyleEnum[$i];
				}
			}
			// Update mode
			if ($_POST['mode'] <= 3 && $_POST['mode'] >= 0) {
				$GLOBALS['db']->execute('UPDATE users_stats SET favourite_mode = ? WHERE username = ?', [$_POST['mode'], $_SESSION['username']]);
			}
			// Save data in db
			$GLOBALS['db']->execute('UPDATE users_stats SET user_color = ?, show_country = ?, username_aka = ?, safe_title = ?, play_style = ? WHERE username = ?', [$c, $_POST['f'], $_POST['aka'], $_POST['st'], $pm, $_SESSION['username']]);
			// Update safe title cookie
			updateSafeTitle();
			// Done, redirect to success page
			redirect('index.php?p=6&s=ok');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=6&e=' . $e->getMessage());
		}
	}
	/*
	 * SaveUserpage
	 * Save userpage functions
	*/
	public static function SaveUserpage() {
		try {
			// Check if we are logged in
			sessionCheck();
			// Check if everything is set
			if (!isset($_POST['c'])) {
				throw new Exception(0);
			}
			// Check userpage length
			if (strlen($_POST['c']) > 1500) {
				throw new Exception(1);
			}
			// Save data in db
			$GLOBALS['db']->execute('UPDATE users_stats SET userpage_content = ? WHERE username = ?', [$_POST['c'], $_SESSION['username']]);
			// Done, redirect to success page
			redirect('index.php?p=8&s=ok');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=8&e=' . $e->getMessage() . $r);
		}
	}
	/*
	 * ChangeAvatar
	 * Chhange avatar functions
	*/
	public static function ChangeAvatar() {
		try {
			// Check if we are logged in
			sessionCheck();
			// Check if everything is set
			if (!isset($_FILES['file'])) {
				throw new Exception(0);
			}
			// Check if image file is a actual image or fake image
			if (!getimagesize($_FILES['file']['tmp_name'])) {
				throw new Exception(1);
			}
			// Allow certain file formats
			$allowedFormats = ['jpg', 'jpeg', 'png'];
			if (!in_array(pathinfo($_FILES['file']['name']) ['extension'], $allowedFormats)) {
				throw new Exception(2);
			}
			// Check file size
			if ($_FILES['file']['size'] > 1000000) {
				throw new Exception(3);
			}
			// Resize
			if (!smart_resize_image($_FILES['file']['tmp_name'], null, 100, 100, false, dirname(dirname(dirname(__FILE__))) . '/a.ppy.sh/avatars/' . getUserID($_SESSION['username']) . '.png', false, false, 100)) {
				throw new Exception(4);
			}
			/* "Convert" to png
												if (!move_uploaded_file($_FILES["file"]["tmp_name"], dirname(dirname(dirname(__FILE__)))."/a.ppy.sh/avatars/".getUserID($_SESSION["username"]).".png")) {
												    throw new Exception(4);
												}*/
			// Done, redirect to success page
			redirect('index.php?p=5&s=ok');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=5&e=' . $e->getMessage());
		}
	}
	/*
	 * SendReport
	 * Send report function
	*/
	public static function SendReport() {
		try {
			// Check if we are logged in
			sessionCheck();
			// Check if everything is set
			if (!isset($_POST['t']) || !isset($_POST['n']) || !isset($_POST['c']) || empty($_POST['n']) || empty($_POST['c'])) {
				throw new Exception(0);
			}
			// Add report
			$GLOBALS['db']->execute('INSERT INTO reports (id, name, from_username, content, type, open_time, update_time, status) VALUES (NULL, ?, ?, ?, ?, ?, ?, 1)', [$_POST['n'], $_SESSION['username'], $_POST['c'], $_POST['t'], time(), time() ]);
			// Webhook stuff
			global $WebHookReport;
			global $KeyAkerino;
			$type = $_POST['t'];
			switch ($type) {
				case 0:
					$type = 'bug';
				break;
				case 1:
					$type = 'feature';
				break;
			}
			post_content_http($WebHookReport, ['key' => $KeyAkerino, 'title' => $_POST['n'], 'content' => $_POST['c'], 'id' => $GLOBALS['db']->lastInsertId(), 'type' => $type, 'username' => $_SESSION['username'], ]);
			// Done, redirect to success page
			redirect('index.php?p=22&s=ok');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=22&e=' . $e->getMessage());
		}
	}
	/*
	 * OpenCloseReport
	 * Open/Close a report (ADMIN CP)
	*/
	public static function OpenCloseReport() {
		try {
			// Check if everything is set
			if (!isset($_GET['id']) || empty($_GET['id'])) {
				throw new Exception('Invalid request');
			}
			// Get current report status from db
			$reportStatus = $GLOBALS['db']->fetch('SELECT status FROM reports WHERE id = ?', [$_GET['id']]);
			// Make sure the report exists
			if (!$reportStatus) {
				throw new Exception("That report doesn't exist");
			}
			// Get report status
			$reportStatus = current($reportStatus);
			// Get new report status
			$newReportStatus = $reportStatus == 1 ? 0 : 1;
			// Edit report status
			$GLOBALS['db']->execute('UPDATE reports SET status = ?, update_time = ? WHERE id = ?', [$newReportStatus, time(), $_GET['id']]);
			// Done, redirect to success page
			redirect('index.php?p=113&s=Report status changed!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=113&e=' . $e->getMessage());
		}
	}
	/*
	 * SaveEditReport
	 * Saves an edited report (ADMIN CP)
	*/
	public static function SaveEditReport() {
		try {
			// Check if everything is set
			if (!isset($_POST['id']) || !isset($_POST['s']) || !isset($_POST['r']) || empty($_POST['id'])) {
				throw new Exception('Invalid request');
			}
			// Get current report status from db
			$reportData = $GLOBALS['db']->fetch('SELECT * FROM reports WHERE id = ?', [$_POST['id']]);
			// Make sure the report exists
			if (!$reportData) {
				throw new Exception("That report doesn't exist");
			}
			// Edit report status
			$GLOBALS['db']->execute('UPDATE reports SET status = ?, response = ?, update_time = ? WHERE id = ?', [$_POST['s'], $_POST['r'], time(), $_POST['id']]);
			// Done, redirect to success page
			redirect('index.php?p=113&s=Report updated!');
		}
		catch(Exception $e) {
			// Redirect to Exception page
			redirect('index.php?p=113&e=' . $e->getMessage());
		}
	}
	/*
	 * AddRemoveFriend
	 * Add remove friends
	*/
	public static function AddRemoveFriend() {
		try {
			// Check if we are logged in
			sessionCheck();
			// Check if everything is set
			if (!isset($_GET['u']) || empty($_GET['u'])) {
				throw new Exception(0);
			}
			// Get our user id
			$uid = getUserID($_SESSION['username']);
			// Add/remove friend
			if (getFriendship($uid, $_GET['u'], true) == 0) {
				addFriend($uid, $_GET['u'], true);
			} else {
				removeFriend($uid, $_GET['u'], true);
			}
			// Done, redirect
			redirect('index.php?u=' . $_GET['u']);
		}
		catch(Exception $e) {
			redirect('index.php?p=99&e=' . $e->getMessage());
		}
	}
}
