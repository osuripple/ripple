<?php
// We aren't calling the class Do because otherwise it would conflict with do { } while ();
class D {

	/*
	* Login
	* Login function
	*/
	static function Login()
	{
		try
		{
			// Check if everything is set
			if (empty($_POST["u"]) || empty($_POST["p"])) {
				throw new Exception(0);
			}

			// 1.5 -- Make sure user exists (if user doen't exist php spams memes)
			$uid = $GLOBALS["db"]->fetch("SELECT id FROM users WHERE username = ?", array($_POST["u"]));
			if (!$uid) {
				throw new Exception(1);
			}

			// Calculate secure password
			$options = array('cost' => 9, 'salt' => base64_decode(current($GLOBALS["db"]->fetch("SELECT salt FROM users WHERE username = ?", $_POST["u"]))) );
			$securePassword = crypt($_POST["p"], "$2y$" . $options["salt"]);

			// Check user/password
			if (!$GLOBALS["db"]->fetch("SELECT id FROM users WHERE username = ? AND password_secure = ?", array($_POST["u"], $securePassword)) ) {
				throw new Exception(1);
			}

			// Ban check
			if ( current($GLOBALS["db"]->fetch("SELECT allowed FROM users WHERE username = ?", array($_POST["u"])) ) === '0') {
				throw new Exception(2);
			}

			// Get username with right case
			$username = current($GLOBALS["db"]->fetch("SELECT username FROM users WHERE id = ?", array(current($uid))));

			// Everything ok, create session and do login stuff
			session_start();
			$_SESSION["username"] = $username;
			$_SESSION["password"] = $securePassword;
			$_SESSION["passwordChanged"] = false;

			// Check if the user requested to be remember. If they did, initialize cookies.
			if (isset($_POST["remember"]) && $_POST["remember"] === "yes") {
				$m = new RememberCookieHandler();
				$m->IssueNew($_SESSION["username"]);
			}

			// Get safe title
			updateSafeTitle();

			// Save latest activity
			updateLatestActivity($_SESSION["username"]);

			// Redirect
			redirect("index.php?p=1");
		}
		catch (Exception $e)
		{
			// Redirect to Exception page
			redirect("index.php?p=2&e=".$e->getMessage());
		}
	}


	/*
	* Register
	* Register function
	*/
	static function Register()
	{
		try
		{
			// Check if everything is set
			if (empty($_POST["u"]) || empty($_POST["p1"]) || empty($_POST["p2"]) || empty($_POST["e"]) || empty($_POST["k"])) {
				throw new Exception(0);
			}

			// Check password length
			if (strlen($_POST["p1"]) < 8) {
				throw new Exception(1);
			}

			// Check if passwords match
			if ($_POST["p1"] != $_POST["p2"]) {
				throw new Exception(2);
			}

			// god damn i hate people
			if (in_array($_POST["p1"], array("123456", "password", "12345678", "qwerty", "abc123", "123456789", "111111", "1234567", "iloveyou", "adobe123", "123123", "admin", "1234567890", "letmein", "photoshop", "1234", "monkey", "shadow", "sunshine", "12345", "password1",
			"princess", "azerty", "trustno1", "000000"))) {
				throw new Exception(3);
			}

			// Check if email is valid
			if (!filter_var($_POST["e"], FILTER_VALIDATE_EMAIL)) {
				throw new Exception(4);
			}

			// Check if username is valid
			if (!preg_match("/^[A-Za-z0-9 _\\-\\[\\]]{3,20}$/i", $_POST["u"])) {
				throw new Exception(5);
			}

			// Check if username is already in db
			if ($GLOBALS["db"]->fetch("SELECT * FROM users WHERE username = ?", $_POST["u"])) {
				throw new Exception(6);
			}

			// Check if email is already in db
			if ($GLOBALS["db"]->fetch("SELECT * FROM users WHERE email = ?", $_POST["e"])) {
				throw new Exception(7);
			}

			// Check if beta key is valid
			if (!$GLOBALS["db"]->fetch("SELECT id FROM beta_keys WHERE key_md5 = ? AND allowed = 1", md5($_POST["k"]))) {
				throw new Exception(8, 1);
			}

			// password_hash options
			$options = array('cost' => 9, 'salt' => base64_decode(base64_encode(mcrypt_create_iv(22, MCRYPT_DEV_URANDOM))));

			// Hash the password, the secure way
			$securePassword = crypt($_POST["p1"], "$2y$" . $options["salt"]);

			// Hash the password, the unsecure md5 way that however must be done because the osu! client requires it.
			// #BlamePeppy2015
			$md5Password = crypt(md5($_POST["p1"]), "$2y$" . $options["salt"]);

			// Put some data into the db
			// 1.5 -- Accounts are already activated (allowed 1) since we don't use osu! ids anymore
			$GLOBALS["db"]->execute("INSERT INTO `users`(id, osu_id, username, password_md5, password_secure, salt, email, register_datetime, rank, allowed) VALUES (NULL, 2, ?, ?, ?, ?, ?, ?, 1, 1);", array($_POST["u"], $md5Password, $securePassword, base64_encode($options["salt"]), $_POST["e"], time(true)));

			// Put some data into users_stats
			$GLOBALS["db"]->execute("INSERT INTO `users_stats`(id, osu_id, username, user_color, user_style, ranked_score_std, playcount_std, total_score_std, ranked_score_taiko, playcount_taiko, total_score_taiko, ranked_score_ctb, playcount_ctb, total_score_ctb, ranked_score_mania, playcount_mania, total_score_mania) VALUES (NULL, 2, ?, 'black', '', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);", $_POST["u"]);

			// 1.5 -- Replace osu_id with id (we don't use osu! id anymore)
			// Get db ai id
			$id = current($GLOBALS["db"]->fetch("SELECT id FROM users WHERE username = ?", $_POST["u"]));
			// Set osu_id to id in users and users_stats
			$GLOBALS["db"]->execute("UPDATE users SET osu_id = ? WHERE username = ?", array($id, $_POST["u"]));
			$GLOBALS["db"]->execute("UPDATE users_stats SET osu_id = ? WHERE username = ?", array($id, $_POST["u"]));

			// Invalidate beta key
			$GLOBALS["db"]->execute("UPDATE beta_keys SET allowed = 0 WHERE key_md5 = ?", md5($_POST["k"]));


			// All fine, done
			redirect("index.php?p=3&s=lmao");
		}
		catch(Exception $e)
		{
			// Redirect to Exception page
			redirect("index.php?p=3&e=".$e->getMessage());
		}
	}

	/*
	* ChangePassword
	* Change password function
	*/
	static function ChangePassword()
	{
		try
		{
			// Check if we are logged in
			sessionCheck();

			// Check if everything is set
			if (empty($_POST["pold"]) || empty($_POST["p1"]) || empty($_POST["p2"])) {
				throw new Exception(0);
			}

			// Check password length
			if (strlen($_POST["p1"]) < 8) {
				throw new Exception(1);
			}

			// Check if passwords match
			if ($_POST["p1"] != $_POST["p2"]) {
				throw new Exception(2);
			}

			// god damn i hate people
			if (isPasswordDumb($_POST["p1"])) {
				throw new Exception(3);
			}

			// Calculate secure password
			$oldOptions = array('cost' => 9, 'salt' => base64_decode(current($GLOBALS["db"]->fetch("SELECT salt FROM users WHERE username = ?", $_SESSION["username"]))) );
			$oldSecurePassword = crypt($_POST["pold"], "$2y$" . $options["salt"]);

			// Check if the current password is the right one
			if (current($GLOBALS["db"]->fetch("SELECT password_secure FROM users WHERE username = ?", $_SESSION["username"])) != $oldSecurePassword) {
				throw new Exception(4);
			}

			// Calculate new secure password
			$newOptions = array('cost' => 9, 'salt' => base64_decode(base64_encode(mcrypt_create_iv(22, MCRYPT_DEV_URANDOM))));
			$newSecurePassword = crypt($_POST["p1"], "$2y$" . $newOptions["salt"]);

			// Calculate new unsecure password
			$newMd5Password = crypt(md5($_POST["p1"]), "$2y$" . $newOptions["salt"]);

			// Change both passwords and salt
			$GLOBALS["db"]->execute("UPDATE users SET password_md5 = ? WHERE username = ?", array($newMd5Password, $_SESSION["username"]));
			$GLOBALS["db"]->execute("UPDATE users SET password_secure = ? WHERE username = ?", array($newSecurePassword, $_SESSION["username"]));
			$GLOBALS["db"]->execute("UPDATE users SET salt = ? WHERE username = ?", array(base64_encode($newOptions["salt"]), $_SESSION["username"]));

			// Set in session that we've changed our password otherwise sessionCheck() will kick us
			$_SESSION["passwordChanged"] = true;

			// Redirect to success page
			redirect("index.php?p=7&s=done");

		}
		catch (Exception $e)
		{
			// Redirect to Exception page
			redirect("index.php?p=7&e=".$e->getMessage());
		}
	}


	/*
	* SetOsuID
	* Set osu! id function (for avatar on main osu! server)
	*/
	static function SetOsuID()
	{
		// We need to be logged in
		sessionCheck();

		try
		{
			// Check if everything is set
			if (!isset($_POST["osuid"]) || empty($_POST["osuid"])) {
				throw new Exception("Nice troll");
			}

			// Check if our osu id is 2 (aka not set)
			if (current($GLOBALS["db"]->fetch("SELECT osu_id FROM users WHERE username = ?", $_SESSION["username"])) != 2) {
				throw new Exception("Osu! id already set.");
			}

			// Check if we are not using an osu! id already used
			if ($GLOBALS["db"]->fetch("SELECT id FROM users WHERE osu_id = ?", $_POST["osuid"])) {
				throw new Exception("Osu! id already taken! If someone has taken your osu! id, contact an admin.");
			}

			// Set our osu! id in users table
			$GLOBALS["db"]->execute("UPDATE users SET osu_id = ? WHERE username = ?", array($_POST["osuid"], $_SESSION["username"]));

			// Set our osu! id in users_stats table
			$GLOBALS["db"]->execute("UPDATE users_stats SET osu_id = ? WHERE username = ?", array($_POST["osuid"], $_SESSION["username"]));

			// Redirect to success page
			redirect("index.php?p=12&s=done");
		}
		catch (Exception $e)
		{
			// Redirect to exception page
			redirect("index.php?p=12&e=".$e->getMessage());
		}
	}


	/*
	* RecoverPassword()
	* Form submission for printPasswordRecovery.
	*/
	static function RecoverPassword() {
		global $MailgunConfig;
		try {
			if (!isset($_POST["username"]) || empty($_POST["username"]))
				throw new Exception(0);
			$username = $_POST["username"];
			$user = $GLOBALS["db"]->fetch("SELECT username, email, allowed FROM users WHERE username = ?", array($username));
			// Check the user actually exists.
			if (!$user)
				throw new Exception(1);
			if ($user["allowed"] == '0')
				throw new Exception(2);
			$key = randomString(80);
			$GLOBALS["db"]->execute("INSERT INTO password_recovery (k, u) VALUES (?, ?);", array($key, $username));
			require_once dirname(__FILE__) . "/SimpleMailgun.php";
			$mailer = new SimpleMailgun($MailgunConfig);
			$mailer->Send(
				"ripple <noreply@ripple.moe>",
				$user["email"],
				"ripple password recovery instructions",
				sprintf("Hey %s! Someone, which we really hope was you, requested a password reset for your account. In case it was you, please <a href='%s'>click here</a> to reset your password on ripple. Otherwise, silently ignore this email.", $username, "http://" . $_SERVER["HTTP_HOST"] . "/index.php?p=19&k=" . $key . "&user=" . $username)
			);
			redirect("index.php?p=18&s=sent");
		}
		catch (Exception $e) {
			redirect("index.php?p=18&e=" . $e->getMessage());
		}
	}


	/*
	* PasswordFinishRecovery()
	* Finishes the password recovery procedure.
	*/
	static function PasswordFinishRecovery() {
		try {
			if (empty($_POST["k"]) || empty($_POST["user"])) {
				throw new Exception(0);
			}
			if (empty($_POST["p1"]) || empty($_POST["p2"])) {
				throw new Exception(0);
			}

			$d = $GLOBALS["db"]->fetch("SELECT id FROM password_recovery WHERE k = ? AND u = ?;", array($_POST["k"], $_POST["user"]));

			if ($d === false) {
				throw new Exception(4);
			}

			// Check password length
			if (strlen($_POST["p1"]) < 8) {
				throw new Exception(1);
			}

			// Check if passwords match
			if ($_POST["p1"] != $_POST["p2"]) {
				throw new Exception(2);
			}

			// god damn i hate people
			if (in_array($_POST["p1"], array("123456", "password", "12345678", "qwerty", "abc123", "123456789", "111111", "1234567", "iloveyou", "adobe123", "123123", "admin", "1234567890", "letmein", "photoshop", "1234", "monkey", "shadow", "sunshine", "12345", "password1", "princess", "azerty", "trustno1", "000000"))) {
				throw new Exception(3);
			}

			// Calculate new secure password
			$newOptions = array('cost' => 9, 'salt' => base64_decode(base64_encode(mcrypt_create_iv(22, MCRYPT_DEV_URANDOM))));
			$newSecurePassword = crypt($_POST["p1"], "$2y$" . $newOptions["salt"]);

			// Calculate new unsecure password
			$newMd5Password = crypt(md5($_POST["p1"]), "$2y$" . $newOptions["salt"]);

			// Change both passwords and salt
			$GLOBALS["db"]->execute("UPDATE users SET password_md5 = ? WHERE username = ?", array($newMd5Password, $_POST["user"]));
			$GLOBALS["db"]->execute("UPDATE users SET password_secure = ? WHERE username = ?", array($newSecurePassword, $_POST["user"]));
			$GLOBALS["db"]->execute("UPDATE users SET salt = ? WHERE username = ?", array(base64_encode($newOptions["salt"]), $_POST["user"]));

			// Delete password reset key
			$GLOBALS["db"]->fetch("DELETE FROM password_recovery WHERE id = ?;", array($d["id"]));

			// Redirect to success page
			redirect("index.php?p=2&s=done");
		}
		catch (Exception $e) {
			redirect("index.php?p=19&e=0");
		}
	}


	/*
	* GenerateBetaKey
	* Generate beta key(s) function
	*/
	static function GenerateBetaKey()
	{
		try
		{
			// Check if everything is set
			if (empty($_POST["n"])) {
				throw new Exception("Nice troll.");
			}

			// Set public value
			$p = isset($_POST["p"]) ? 1 : 0;

			// We store plain keys here to show them at the end
			$plainKeys = "";

			// Generate all the keys
			for ($i=0; $i < $_POST["n"]; $i++)
			{
				$d = false;
				while ($d == false)
				{
					$key = generateKey();
					$hash = md5($key);
					if (!$GLOBALS["db"]->fetch("SELECT * FROM beta_keys WHERE key_md5 = ?", $hash)) {
						$GLOBALS["db"]->execute("INSERT INTO beta_keys(key_md5, description, allowed, public) VALUES (?, ?, ?, ?);", array($hash, str_replace("*key*", $key, $_POST["d"]), 1, $p));
						$d = true;
						$plainKeys = $plainKeys."<br>".$key;
					}
					else {
						$d = false;
					}
				}
			}

			// Beta keys generated, go to done page
			redirect("index.php?p=105&s=<b>Beta keys generated!</b>".$plainKeys);
		}
		catch(Exception $e)
		{
			// Redirect to Exception page
			redirect("index.php?p=105&e=".$e->getMessage());
		}
	}


	/*
	* AllowDisallowBetaKey
	* Allow/Disallow beta key function (ADMIN CP)
	*/
	static function AllowDisallowBetaKey()
	{
		try
		{
			// Check if everything is set
			if (empty($_GET["id"])) {
				throw new Exception("Nice troll.");
			}

			// Get current allowed value of this beta key
			$allowed = current($GLOBALS["db"]->fetch("SELECT allowed FROM beta_keys WHERE id = ?", $_GET["id"]));

			// Get new allowed value
			if ($allowed == 1) $newAllowed = 0; else $newAllowed = 1;

			// Change allowed value
			$GLOBALS["db"]->execute("UPDATE beta_keys SET allowed = ? WHERE id = ?", array($newAllowed, $_GET["id"]));

			// Done, redirect to success page
			redirect("index.php?p=105&s=Allowed value changed!");
		}
		catch(Exception $e)
		{
			// Redirect to Exception page
			redirect("index.php?p=105&e=".$e->getMessage());
		}
	}


	/*
	* PublicPrivateBetaKey
	* Public/private beta key function (ADMIN CP)
	*/
	static function PublicPrivateBetaKey()
	{
		try
		{
			// Check if everything is set
			if (empty($_GET["id"])) {
				throw new Exception("Nice troll.");
			}

			// Get current public value of this beta key
			$public = current($GLOBALS["db"]->fetch("SELECT public FROM beta_keys WHERE id = ?", $_GET["id"]));

			// Get new public value
			if ($public == 1) $newPublic = 0; else $newPublic = 1;

			// Change allowed value
			$GLOBALS["db"]->execute("UPDATE beta_keys SET public = ? WHERE id = ?", array($newPublic, $_GET["id"]));

			// Done, redirect to success page
			redirect("index.php?p=105&s=Public value changed!");
		}
		catch(Exception $e)
		{
			// Redirect to Exception page
			redirect("index.php?p=105&e=".$e->getMessage());
		}
	}

	/*
	* RemoveBetaKey
	* Remove beta key function (ADMIN CP)
	*/
	static function RemoveBetaKey()
	{
		try
		{
			// Check if everything is set
			if (empty($_GET["id"])) {
				throw new Exception("Nice troll.");
			}

			// Make sure that this key exists
			$exists = $GLOBALS["db"]->fetch("SELECT * FROM beta_keys WHERE id = ?", $_GET["id"]);

			// Beta key doesn't exists wtf
			if (!$exists) {
				throw new Exception("This beta key doesn\'t exists");
			}

			// Delete beta key
			$GLOBALS["db"]->execute("DELETE FROM beta_keys WHERE id = ?", $_GET["id"]);

			// Done, redirect to success page
			redirect("index.php?p=105&s=Beta key deleted!");
		}
		catch(Exception $e)
		{
			// Redirect to Exception page
			redirect("index.php?p=105&e=".$e->getMessage());
		}
	}


	/*
	* SaveSystemSettings
	* Save system settings function (ADMIN CP)
	*/
	static function SaveSystemSettings()
	{
		try
		{
			// Get values
			if (isset($_POST["wm"])) $wm = $_POST["wm"]; else $wm = 0;
			if (isset($_POST["gm"])) $gm = $_POST["gm"]; else $gm = 0;
			if (isset($_POST["r"]))	$r = $_POST["r"]; else $r = 0;
			if (!empty($_POST["ga"])) $ga = $_POST["ga"]; else $ga = "";
			if (!empty($_POST["ha"])) $ha = $_POST["ha"]; else $ha = "";

			// Save new values
			$GLOBALS["db"]->execute("UPDATE system_settings SET value_int = ? WHERE name = 'website_maintenance'", array($wm));
			$GLOBALS["db"]->execute("UPDATE system_settings SET value_int = ? WHERE name = 'game_maintenance'", array($gm));
			$GLOBALS["db"]->execute("UPDATE system_settings SET value_int = ? WHERE name = 'registrations_enabled'", array($r));
			$GLOBALS["db"]->execute("UPDATE system_settings SET value_string = ? WHERE name = 'website_global_alert'", array($ga));
			$GLOBALS["db"]->execute("UPDATE system_settings SET value_string = ? WHERE name = 'website_home_alert'", array($ha));

			// Done, redirect to success page
			redirect("index.php?p=101&s=Settings saved!");
		}
		catch(Exception $e)
		{
			// Redirect to Exception page
			redirect("index.php?p=101&e=".$e->getMessage());
		}
	}

	/*
	* SaveBanchoSettings
	* Save bancho settings function (ADMIN CP)
	*/
	static function SaveBanchoSettings()
	{
		try
		{
			// Get values
			if (isset($_POST["bm"])) $bm = $_POST["bm"]; else $bm = 0;
			if (isset($_POST["od"])) $od = $_POST["od"]; else $od = 0;
			if (isset($_POST["rm"])) $rm = $_POST["rm"]; else $rm = 0;
			if (!empty($_POST["mi"])) $mi = $_POST["mi"]; else $mi = "";
			if (!empty($_POST["lm"])) $lm = $_POST["lm"]; else $lm = "";
			if (!empty($_POST["ln"])) $ln = $_POST["ln"]; else $ln = "";

			// Save new values
			$GLOBALS["db"]->execute("UPDATE bancho_settings SET value_int = ? WHERE name = 'bancho_maintenance'", array($bm));
			$GLOBALS["db"]->execute("UPDATE bancho_settings SET value_int = ? WHERE name = 'free_direct'", array($od));
			$GLOBALS["db"]->execute("UPDATE bancho_settings SET value_int = ? WHERE name = 'restricted_joke'", array($rm));
			$GLOBALS["db"]->execute("UPDATE bancho_settings SET value_string = ? WHERE name = 'menu_icon'", array($mi));
			$GLOBALS["db"]->execute("UPDATE bancho_settings SET value_string = ? WHERE name = 'login_messages'", array($lm));
			$GLOBALS["db"]->execute("UPDATE bancho_settings SET value_string = ? WHERE name = 'login_notification'", array($ln));

			// Done, redirect to success page
			redirect("index.php?p=111&s=Settings saved!");
		}
		catch(Exception $e)
		{
			// Redirect to Exception page
			redirect("index.php?p=111&e=".$e->getMessage());
		}
	}

	/*
	* RunCron
	* Runs cron.php from admin cp with exec/redirect
	*/
	static function RunCron()
	{
		if ($CRON["adminExec"])
		{
			// kwisk master linux shell pr0
			exec(PHP_BIN_DIR . "/php " . dirname(__FILE__) . "/../cron.php 2>&1 > /dev/null &");
		}
		else
		{
			// Run from browser
			redirect("./cron.php");
		}
	}


	/*
	* SaveEditUser
	* Save edit user function (ADMIN CP)
	*/
	static function SaveEditUser()
	{
		try
		{
			// Check if everything is set (username color, username style, rank and allowed can be empty)
			if (!isset($_POST["id"]) || !isset($_POST["oid"]) || !isset($_POST["u"]) || !isset($_POST["e"]) || !isset($_POST["up"]) || !isset($_POST["aka"]) || !isset($_POST["se"]) || !isset($_POST["sr"])
			|| empty($_POST["id"]) || empty($_POST["oid"]) || empty($_POST["u"]) || empty($_POST["e"]) ) {
				throw new Exception("Nice troll");
			}

			// Check if this user exists
			$id = current($GLOBALS["db"]->fetch("SELECT id FROM users WHERE id = ?", $_POST["id"]));
			if (!$id) {
				throw new Exception("That user doesn\'t exists");
			}

			// Check if we can edit this user
			if (getUserRank($_POST["u"]) >= getUserRank($_SESSION["username"]) && $_POST["u"] != $_SESSION["username"]) {
				throw new Exception("You dont't have enough permissions to edit this user");
			}

			// Check if email is valid
			if (!filter_var($_POST["e"], FILTER_VALIDATE_EMAIL)) {
				throw new Exception("The email isn't valid");
			}

			// Check if silence end has changed. if so, we have to kick the client
			// in order to silence him
			//$oldse = current($GLOBALS["db"]->fetch("SELECT silence_end FROM users WHERE username = ?", array($_POST["u"])));

			// Save new data (osu_id, email, silence end and silence reason)
			$GLOBALS["db"]->execute("UPDATE users SET osu_id = ?, email = ?, silence_end = ?, silence_reason = ? WHERE id = ?", array($_POST["oid"], $_POST["e"], $_POST["se"], $_POST["sr"], $_POST["id"]));

			// Save new userpage
			$GLOBALS["db"]->execute("UPDATE users_stats SET userpage_content = ? WHERE id = ?", array($_POST["up"], $_POST["id"]));

			// Save new data if set (rank, allowed, UP and silence)
			if (isset($_POST["r"]) && !empty($_POST["r"]))
			$GLOBALS["db"]->execute("UPDATE users SET rank = ? WHERE id = ?", array($_POST["r"], $_POST["id"]));

			if (isset($_POST["a"]) )
			$GLOBALS["db"]->execute("UPDATE users SET allowed = ? WHERE id = ?", array($_POST["a"], $_POST["id"]));

			// Get username style/color
			if (isset($_POST["c"]) && !empty($_POST["c"]))
			$c = $_POST["c"];
			else
			$c = "black";

			if (isset($_POST["bg"]) && !empty($_POST["bg"]))
			$bg = $_POST["bg"];
			else
			$bg = "";

			// Set username style/color/aka
			$GLOBALS["db"]->execute("UPDATE users_stats SET user_color = ?, user_style = ?, username_aka = ? WHERE osu_id = ?", array($c, $bg, $_POST["aka"], $_POST["oid"]));

			// Check if silence end has changed, if so we have to kick the user
			//if ($_POST["se"] != $oldse)
			//	kickUser($id);

			// Done, redirect to success page
			redirect("index.php?p=102&s=User edited!");
		}
		catch(Exception $e)
		{
			// Redirect to Exception page
			redirect("index.php?p=102&e=".$e->getMessage());
		}
	}


	/*
	* BanUnbanUser
	* Ban/Unban user function (ADMIN CP)
	*/
	static function BanUnbanUser()
	{
		try
		{
			// Check if everything is set
			if (empty($_GET["id"])) {
				throw new Exception("Nice troll.");
			}

			// Get username
			$username = current($GLOBALS["db"]->fetch("SELECT username FROM users WHERE id = ?", $_GET["id"]));

			// Check if we can ban this user
			if (getUserRank($username) >= getUserRank($_SESSION["username"])) {
				throw new Exception("You dont't have enough permissions to ban this user");
			}

			// Get current allowed value of this user
			$allowed = current($GLOBALS["db"]->fetch("SELECT allowed FROM users WHERE id = ?", $_GET["id"]));

			// Get new allowed value
			if ($allowed == 1) $newAllowed = 0; else $newAllowed = 1;

			// Change allowed value
			$GLOBALS["db"]->execute("UPDATE users SET allowed = ? WHERE id = ?", array($newAllowed, $_GET["id"]));

			// Done, redirect to success page
			redirect("index.php?p=102&s=User banned/unbanned/activated!");
		}
		catch(Exception $e)
		{
			// Redirect to Exception page
			redirect("index.php?p=102&e=".$e->getMessage());
		}
	}

	/*
	* QuickEditUser
	* Redirects to the edit user page for the user with $_POST["u"] username
	*/
	static function QuickEditUser()
	{
		try
		{
			// Check if everything is set
			if (empty($_POST["u"])) {
				throw new Exception("Nice troll.");
			}

			// Get user id
			$id = current($GLOBALS["db"]->fetch("SELECT id FROM users WHERE username = ?", $_POST["u"]));

			// Check if that user exists
			if (!$id) {
				throw new Exception("That user doesn't exists");
			}

			// Done, redirect to edit page
			redirect("index.php?p=103&id=".$id);
		}
		catch(Exception $e)
		{
			// Redirect to Exception page
			redirect("index.php?p=102&e=".$e->getMessage());
		}
	}

	/*
	* QuickEditUserBadges
	* Redirects to the edit user badges page for the user with $_POST["u"] username
	*/
	static function QuickEditUserBadges()
	{
		try
		{
			// Check if everything is set
			if (empty($_POST["u"])) {
				throw new Exception("Nice troll.");
			}

			// Get user id
			$id = current($GLOBALS["db"]->fetch("SELECT id FROM users WHERE username = ?", $_POST["u"]));

			// Check if that user exists
			if (!$id) {
				throw new Exception("That user doesn't exists");
			}

			// Done, redirect to edit page
			redirect("index.php?p=110&id=".$id);
		}
		catch(Exception $e)
		{
			// Redirect to Exception page
			redirect("index.php?p=108&e=".$e->getMessage());
		}
	}

	/*
	* ChangeIdentity
	* Change identity function (ADMIN CP)
	*/
	static function ChangeIdentity()
	{
		try
		{
			// Check if everything is set
			if (!isset($_POST["id"]) || !isset($_POST["oldu"]) || !isset($_POST["newu"]) || !isset($_POST["oldoid"]) || !isset($_POST["newoid"]) || !isset($_POST["ks"])
			|| empty($_POST["id"]) || empty($_POST["oldu"]) || empty($_POST["newu"]) || empty($_POST["oldoid"]) || empty($_POST["newoid"])) {
				throw new Exception("Nice troll.");
			}

			// Check if we can edit this user
			if (getUserRank($_POST["oldu"]) >= getUserRank($_SESSION["username"]) && $_POST["oldu"] != $_SESSION["username"]) {
				throw new Exception("You dont't have enough permissions to edit this user");
			}

			// Change stuff
			$GLOBALS["db"]->execute("UPDATE users SET username = ?, osu_id = ? WHERE id = ?", array($_POST["newu"], $_POST["newoid"], $_POST["id"]));
			$GLOBALS["db"]->execute("UPDATE users_stats SET username = ?, osu_id = ? WHERE id = ?", array($_POST["newu"], $_POST["newoid"], $_POST["id"]));

			// Change username in scores if needed
			if ($_POST["ks"] == 1)
			$GLOBALS["db"]->execute("UPDATE scores SET username = ? WHERE username = ?", array($_POST["newu"], $_POST["oldu"]));

			// Done, redirect to success page
			redirect("index.php?p=102&s=User identity changed!");
		}
		catch(Exception $e)
		{
			// Redirect to Exception page
			redirect("index.php?p=102&e=".$e->getMessage());
		}
	}


	/*
	* SaveDocFile
	* Save doc file function (ADMIN CP)
	*/
	static function SaveDocFile()
	{
		try
		{
			// Check if everything is set
			if (!isset($_POST["id"]) || !isset($_POST["t"]) || !isset($_POST["c"]) || !isset($_POST["p"])
			|| empty($_POST["t"]) || empty($_POST["c"])) {
				throw new Exception("Nice troll.");
			}

			// Check if we are creating or editing a doc page
			if ($_POST["id"] == 0)
			$GLOBALS["db"]->execute("INSERT INTO docs (id, doc_name, doc_contents, public) VALUES (NULL, ?, ?, ?)", array($_POST["t"], $_POST["c"], $_POST["p"]));
			else
			$GLOBALS["db"]->execute("UPDATE docs SET doc_name = ?, doc_contents = ?, public = ? WHERE id = ?", array($_POST["t"], $_POST["c"], $_POST["p"], $_POST["id"]));

			// Done, redirect to success page
			redirect("index.php?p=106&s=Documentation page edited!");
		}
		catch(Exception $e)
		{
			// Redirect to Exception page
			redirect("index.php?p=106&e=".$e->getMessage());
		}
	}


	/*
	* SaveBadge
	* Save badge function (ADMIN CP)
	*/
	static function SaveBadge()
	{
		try
		{
			// Check if everything is set
			if (!isset($_POST["id"]) || !isset($_POST["n"]) || !isset($_POST["i"]) ||
			empty($_POST["n"]) || empty($_POST["i"])) {
				throw new Exception("Nice troll.");
			}

			// Check if we are creating or editing a doc page
			if ($_POST["id"] == 0)
			$GLOBALS["db"]->execute("INSERT INTO badges (id, name, icon) VALUES (NULL, ?, ?)", array($_POST["n"], $_POST["i"]));
			else
			$GLOBALS["db"]->execute("UPDATE badges SET name = ?, icon = ? WHERE id = ?", array($_POST["n"], $_POST["i"], $_POST["id"]));

			// Done, redirect to success page
			redirect("index.php?p=108&s=Badge edited!");
		}
		catch(Exception $e)
		{
			// Redirect to Exception page
			redirect("index.php?p=108&e=".$e->getMessage());
		}
	}


	/*
	* SaveUserBadges
	* Save user badges function (ADMIN CP)
	*/
	static function SaveUserBadges()
	{
		try
		{
			// Check if everything is set
			if (!isset($_POST["u"]) || !isset($_POST["b01"]) || !isset($_POST["b02"]) || !isset($_POST["b03"]) || !isset($_POST["b04"]) || !isset($_POST["b05"]) || !isset($_POST["b06"]) || empty($_POST["u"])) {
				throw new Exception("Nice troll.");
			}

			// Make sure that this user exists
			if (!$GLOBALS["db"]->fetch("SELECT id FROM users WHERE username = ?", $_POST["u"])) {
				throw new Exception("That user doesn't exists.");
			}

			// Get the string with all the badges
			$badgesString = $_POST["b01"].",".$_POST["b02"].",".$_POST["b03"].",".$_POST["b04"].",".$_POST["b05"].",".$_POST["b06"];

			// Save the new badges string
			$GLOBALS["db"]->execute("UPDATE users_stats SET badges_shown = ? WHERE username = ?", array($badgesString, $_POST["u"]));

			// Done, redirect to success page
			redirect("index.php?p=108&s=Badge edited!");
		}
		catch(Exception $e)
		{
			// Redirect to Exception page
			redirect("index.php?p=108&e=".$e->getMessage());
		}
	}


	/*
	* RemoveDocFile
	* Delete doc file function (ADMIN CP)
	*/
	static function RemoveDocFile()
	{
		try
		{
			// Check if everything is set
			if (!isset($_GET["id"]) || empty($_GET["id"])) {
				throw new Exception("Nice troll.");
			}

			// Check if this doc page exists
			if (!$GLOBALS["db"]->fetch("SELECT * FROM docs WHERE id = ?", $_GET["id"])) {
				throw new Exception("That documentation page doesn't exists");
			}

			// Delete doc page
			$GLOBALS["db"]->execute("DELETE FROM docs WHERE id = ?", $_GET["id"]);

			// Done, redirect to success page
			redirect("index.php?p=106&s=Documentation page deleted!");
		}
		catch(Exception $e)
		{
			// Redirect to Exception page
			redirect("index.php?p=106&e=".$e->getMessage());
		}
	}


	/*
	* RemoveBadge
	* Remove badge function (ADMIN CP)
	*/
	static function RemoveBadge()
	{
		try
		{
			// Make sure that this is not the "None badge"
			if (empty($_GET["id"])) {
				throw new Exception("You can't delete this badge.");
			}

			// Make sure that this badge exists
			$exists = $GLOBALS["db"]->fetch("SELECT * FROM badges WHERE id = ?", $_GET["id"]);

			// Beta key doesn't exists wtf
			if (!$exists) {
				throw new Exception("This badge doesn't exists");
			}

			// Delete badge
			$GLOBALS["db"]->execute("DELETE FROM badges WHERE id = ?", $_GET["id"]);

			// Done, redirect to success page
			redirect("index.php?p=108&s=Badge deleted!");
		}
		catch(Exception $e)
		{
			// Redirect to Exception page
			redirect("index.php?p=108&e=".$e->getMessage());
		}
	}


	/*
	* SilenceUser
	* Silence someone (ADMIN CP)
	*/
	static function SilenceUser()
	{
		try
		{
			// Check if everything is set
			if (!isset($_POST["u"]) || !isset($_POST["c"]) || !isset($_POST["un"]) || !isset($_POST["r"]) || empty($_POST["u"]) || empty($_POST["c"]) || empty($_POST["un"]) || empty($_POST["r"])) {
				throw new Exception("Invalid request");
			}

			// Get user id
			$id = current($GLOBALS["db"]->fetch("SELECT id FROM users WHERE username = ?", $_POST["u"]));

			// Check if that user exists
			if (!$id) {
				throw new Exception("That user doesn't exists");
			}

			// Calculate silence period length
			$sl = $_POST["c"]*$_POST["un"];

			// Make sure silence time is less than 7 days
			if ($sl > 604800) {
				throw new Exception("Invalid silence length. Maximum silence length is 7 days.");
			}

			// Silence and reconnect that user
			silenceUser($id, time()+$sl, $_POST["r"]);
			kickUser($id);

			// Done, redirect to success page
			redirect("index.php?p=102&s=User silenced!");
		}
		catch(Exception $e)
		{
			// Redirect to Exception page
			redirect("index.php?p=102&e=".$e->getMessage());
		}
	}

	/*
	* KickUser
	* Kick someone from bancho (ADMIN CP)
	*/
	static function KickUser()
	{
		try
		{
			// Check if everything is set
			if (!isset($_POST["u"]) || empty($_POST["u"])) {
				throw new Exception("Invalid request");
			}

			// Get user id
			$id = current($GLOBALS["db"]->fetch("SELECT id FROM users WHERE username = ?", $_POST["u"]));

			// Check if that user exists
			if (!$id) {
				throw new Exception("That user doesn't exists");
			}

			// Kick that user
			kickUser($id);

			// Done, redirect to success page
			redirect("index.php?p=102&s=User kicked!");
		}
		catch(Exception $e)
		{
			// Redirect to Exception page
			redirect("index.php?p=102&e=".$e->getMessage());
		}
	}

	/*
	* ResetAvatar
	* Reset soneone's avatar (ADMIN CP)
	*/
	static function ResetAvatar()
	{
		try
		{
			// Check if everything is set
			if (!isset($_GET["id"]) || empty($_GET["id"])) {
				throw new Exception("Invalid request");
			}

			// Get user id
			$avatar = dirname(dirname(dirname(__FILE__)))."/a.ppy.sh/avatars/".$_GET["id"].".png";
			if (!file_exists($avatar)) {
				throw new Exception("That user doesn't have an avatar");
			}

			// Delete user avatar
			unlink($avatar);

			// Done, redirect to success page
			redirect("index.php?p=102&s=Avatar reset!");
		}
		catch(Exception $e)
		{
			// Redirect to Exception page
			redirect("index.php?p=102&e=".$e->getMessage());
		}
	}

	/*
	 * Logout
	 * Logout and return to home
	 */
	static function Logout()
	{
		// Logging out without being logged in doesn't make much sense
		if (checkLoggedIn())
		{
			startSessionIfNotStarted();
			if (isset($_COOKIE["s"]) && isset($_COOKIE["t"])) {
			$rch = new RememberCookieHandler();
				// Desu-troy permanent session.
				$rch->Destroy($_COOKIE["s"]);
				$rch->UnsetCookies();
			}
			$_SESSION = array();
			session_destroy();
		}
		else
		{
			// Uhm, some kind of error/h4xx0r. Let's return to login page just because yes.
			redirect("index.php?p=2");
		}
	}

	/*
	 * ForgetEveryCookie
	 * Allows the user to delete every field in the remember database table with their username, so that it is logged out of every computer they were logged in.
	 */
	static function ForgetEveryCookie() {
		startSessionIfNotStarted();
		$rch = new RememberCookieHandler();
		$rch->DestroyAll($_SESSION["username"]);
		redirect("index.php?p=1&s=forgetDone");
	}


	/*
	* saveUserSettings
	* Save user settings functions
	*/
	static function saveUserSettings()
	{
		global $PlayStyleEnum;
		try
		{
			// Check if we are logged in
			sessionCheck();

			// Check if everything is set
			if (!isset($_POST["f"]) || !isset($_POST["c"]) || !isset($_POST["aka"]) || !isset($_POST["st"])) {
				throw new Exception(0);
			}

			// Check if username color is not empty and if so, set to black (default)
			if (empty($_POST["c"]))
				$c = "black";
			else
				$c = $_POST["c"];

			// Playmode stuff
			$pm = 0;
			foreach ($_POST as $key => $value) {
				$i = str_replace("_", " ", substr($key, 3));
				if ($value == 1 && substr($key, 0, 3) == "ps_" && isset($PlayStyleEnum[$i])) {
					$pm += $PlayStyleEnum[$i];
				}
			}

			// Save data in db
			$GLOBALS["db"]->execute("UPDATE users_stats SET user_color = ?, show_country = ?, username_aka = ?, safe_title = ?, play_style = ? WHERE username = ?", array($c, $_POST["f"], $_POST["aka"], $_POST["st"], $pm, $_SESSION["username"]));

			// Update safe title cookie
			updateSafeTitle();

			// Done, redirect to success page
			redirect("index.php?p=6&s=ok");
		}
		catch(Exception $e)
		{
			// Redirect to Exception page
			redirect("index.php?p=6&e=".$e->getMessage());
		}
	}

	/*
	* SaveUserpage
	* Save userpage functions
	*/
	static function SaveUserpage()
	{
		try
		{
			// Check if we are logged in
			sessionCheck();

			// Check if everything is set
			if (!isset($_POST["c"])) {
				throw new Exception(0);
			}

			// Check userpage length
			if (strlen($_POST["c"]) > 1500) {
				throw new Exception(1);
			}

			// Save data in db
			$GLOBALS["db"]->execute("UPDATE users_stats SET userpage_content = ? WHERE username = ?", array($_POST["c"], $_SESSION["username"]));

			// Done, redirect to success page
			redirect("index.php?p=8&s=ok");
		}
		catch(Exception $e)
		{
			// Redirect to Exception page
			redirect("index.php?p=8&e=".$e->getMessage().$r);
		}
	}

	/*
	* ChangeAvatar
	* Chhange avatar functions
	*/
	static function ChangeAvatar()
	{
		try
		{
			// Check if we are logged in
			sessionCheck();

			// Check if everything is set
			if (!isset($_FILES["file"])) {
				throw new Exception(0);
			}

			// Check if image file is a actual image or fake image
			if(!getimagesize($_FILES["file"]["tmp_name"])) {
				throw new Exception(1);
			}

			// Allow certain file formats
			$allowedFormats = array("jpg", "jpeg", "png");
			if(!in_array(pathinfo($_FILES["file"]["name"])["extension"], $allowedFormats)) {
				throw new Exception(2);
			}

			// Check file size
			if ($_FILES["file"]["size"] > 1000000) {
				throw new Exception(3);
			}

			// Resize
			if(!smart_resize_image($_FILES["file"]["tmp_name"], null, 100, 100, false , dirname(dirname(dirname(__FILE__)))."/a.ppy.sh/avatars/".getUserOsuID($_SESSION["username"]).".png", false, false, 100)) {
				throw new Exception(4);
			}

			/* "Convert" to png
			if (!move_uploaded_file($_FILES["file"]["tmp_name"], dirname(dirname(dirname(__FILE__)))."/a.ppy.sh/avatars/".getUserOsuID($_SESSION["username"]).".png")) {
				throw new Exception(4);
			}*/

			// Done, redirect to success page
			redirect("index.php?p=5&s=ok");
		}
		catch(Exception $e)
		{
			// Redirect to Exception page
			redirect("index.php?p=5&e=".$e->getMessage());
		}
	}

	/*
	* SendReport
	* Send report function
	*/
	static function SendReport()
	{
		try
		{
			// Check if we are logged in
			sessionCheck();

			// Check if everything is set
			if (!isset($_POST["t"]) || !isset($_POST["n"]) || !isset($_POST["c"]) || empty($_POST["n"]) || empty($_POST["c"])) {
				throw new Exception(0);
			}

			// Add report
			$GLOBALS["db"]->execute("INSERT INTO reports (id, name, from_username, content, type, open_time, update_time, status) VALUES (NULL, ?, ?, ?, ?, ?, ?, 1)", array($_POST["n"], $_SESSION["username"], $_POST["c"], $_POST["t"], time(), time()));

			// Done, redirect to success page
			redirect("index.php?p=22&s=ok");
		}
		catch(Exception $e)
		{
			// Redirect to Exception page
			redirect("index.php?p=22&e=".$e->getMessage());
		}
	}

	/*
	* OpenCloseReport
	* Open/Close a report (ADMIN CP)
	*/
	static function OpenCloseReport()
	{
		try
		{
			// Check if everything is set
			if (!isset($_GET["id"]) || empty($_GET["id"])) {
				throw new Exception("Invalid request");
			}

			// Get current report status from db
			$reportStatus = $GLOBALS["db"]->fetch("SELECT status FROM reports WHERE id = ?", array($_GET["id"]));

			// Make sure the report exists
			if (!$reportStatus) {
				throw new Exception("That report doesn't exist");
			}

			// Get report status
			$reportStatus = current($reportStatus);

			// Get new report status
			$newReportStatus = $reportStatus == 1 ? 0 : 1;

			// Edit report status
			$GLOBALS["db"]->execute("UPDATE reports SET status = ?, update_time = ? WHERE id = ?", array($newReportStatus, time(), $_GET["id"]));

			// Done, redirect to success page
			redirect("index.php?p=113&s=Report status changed!");
		}
		catch(Exception $e)
		{
			// Redirect to Exception page
			redirect("index.php?p=113&e=".$e->getMessage());
		}
	}


	/*
	* SaveEditReport
	* Saves an edited report (ADMIN CP)
	*/
	static function SaveEditReport()
	{
		try
		{
			// Check if everything is set
			if (!isset($_POST["id"]) || !isset($_POST["s"]) || !isset($_POST["r"]) || empty($_POST["id"])) {
				throw new Exception("Invalid request");
			}

			// Get current report status from db
			$reportData = $GLOBALS["db"]->fetch("SELECT * FROM reports WHERE id = ?", array($_POST["id"]));

			// Make sure the report exists
			if (!$reportData) {
				throw new Exception("That report doesn't exist");
			}

			// Edit report status
			$GLOBALS["db"]->execute("UPDATE reports SET status = ?, response = ?, update_time = ? WHERE id = ?", array($_POST["s"], $_POST["r"], time(), $_POST["id"]));

			// Done, redirect to success page
			redirect("index.php?p=113&s=Report updated!");
		}
		catch(Exception $e)
		{
			// Redirect to Exception page
			redirect("index.php?p=113&e=".$e->getMessage());
		}
	}
}
