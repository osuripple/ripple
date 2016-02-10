<?php
class Login {
	const PageID = 2;
	const URL = "login";
	const Title = "Ripple - Login";

	public $mh_POST = array(
		"u",
		"p",
	);

	public $error_messages = array(
		"Nice troll.",
		"Wrong username or password.",
		"You are banned from ripple. Do not come back.",
		"You are not logged in.",
		"Session expired. Please login again.",
		"Invalid auto-login cookie.",
		"You are already logged in.",
	);
	public $success_messages = array(
		"All right, sunshine! Your password is now changed. Why don't you login with your shiny new password, now?",
	);

	public function Print() {
		if (checkLoggedIn()) {
			redirect("index.php?p=1&e=1");
		}
		echo('<br><div id="narrow-content"><h1><i class="fa fa-sign-in"></i>	Login</h1>');

		if (!isset($_GET["e"]) && !isset($_GET["s"]))
			echo('<p>Please enter your credentials.</p>');

		echo('<p><a href="index.php?p=18">Forgot your password, perhaps?</a></p>');

		// Print login form
		echo('<form action="submit.php" method="POST">
		<input name="action" value="login" hidden>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-user" max-width="25%"></span></span><input type="text" name="u" required class="form-control" placeholder="Username" aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-lock" max-width="25%"></span></span><input type="password" name="p" required class="form-control" placeholder="Password" aria-describedby="basic-addon1"></div>
		<p style="line-height: 15px"></p>
		<p><label><input type="checkbox" name="remember" value="yes"> Stay logged in?</label></p>
		<p style="line-height: 15px"></p>
		<button type="submit" class="btn btn-primary">Login</button>
		<a href="index.php?p=3" type="button" class="btn btn-default">Sign up</a>
		</form>
		</div>');
	}
	public function Do() {
		$d = $this->DoGetData();
		if (isset($d["success"])) {
			redirect("index.php?p=1");
		} else {
			redirect("index.php?p=1&e=" . $d["error"]);
		}
	}
	public function PrintGetData() {
		return array();
	}
	public function DoGetData() {
		$ret = array();
		try
		{
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

			$ret["success"] = true;
		}
		catch (Exception $e)
		{
			$ret["error"] = $e->getMessage();
		}
		return $ret;
	}
}
