<?php
class Login {
	const PageID = 2;
	const URL = "login";
	const Title = "Ripple - Login";
	const LoggedIn = false;

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

	public function P() {
		clir(TRUE, "index.php?p=1&e=1");
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
	public function D() {
		$d = $this->DoGetData();
		if (isset($d["success"])) {
			redirect("index.php?p=1");
		} else {
			redirect("index.php?p=2&e=" . $d["error"]);
		}
	}
	public function PrintGetData() {
		return array();
	}
	public function DoGetData() {
		$ret = array();
		try
		{
			if (!PasswordHelper::CheckPass($_POST["u"], $_POST["p"], false))
				throw new Exception(1);

			$us = $GLOBALS["db"]->fetch("SELECT allowed, password_md5, username FROM users WHERE username = ?", array($_POST["u"]));
			// Ban check
			if ( $us["allowed"] === '0') {
				throw new Exception(2);
			}

			// Get username with right case
			$username = $us["username"];

			// Everything ok, create session and do login stuff
			session_start();
			$_SESSION["username"] = $username;
			$_SESSION["password"] = $us["password_md5"];
			$_SESSION["passwordChanged"] = false;

			// Check if the user requested to be remembered. If they did, initialise cookies.
			if (isset($_POST["remember"]) && (bool)$_POST["remember"]) {
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
