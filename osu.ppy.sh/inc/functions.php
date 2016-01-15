<?php
	/*
	 * Ripple functions file
	 * include this to include the world
	 */

	// Include config file and db class
	require_once(dirname(__FILE__) . "/config.php");
	require_once(dirname(__FILE__) . "/db.php");
	require_once(dirname(__FILE__) . "/password_compat.php");
	require_once(dirname(__FILE__) . "/Do.php");
	require_once(dirname(__FILE__) . "/Print.php");
	require_once(dirname(__FILE__) . "/RememberCookieHandler.php");
	require_once dirname(__FILE__) . "/PlayStyleEnum.php";

	// Set timezone to UTC
	date_default_timezone_set('Europe/Rome');

	// Connect to MySQL Database
	$GLOBALS["db"] = new DBPDO();


	/****************************************
	 **			GENERAL FUNCTIONS 		   **
	 ****************************************/

	/*
	 * redirect
	 * Redirects to a URL.
	 *
	 * @param (string) ($url) Destination URL.
	 */
	function redirect($url) {
		header('Location: '.$url);
		exit();
	}


	/*
	 * outputVariable
	 * Output $v variable to $fn file
	 * Only for debugging purposes
	 *
	 * @param (string) ($fn) Output file name
	 * @param ($v) Variable to output
	 */
	function outputVariable($fn, $v)
	{
		file_put_contents($fn, print_r($v, true), FILE_APPEND);
	}


	/*
	 * randomString
	 * Generate a random string.
	 * Used to get screenshot id in osu-screenshot.php
	 *
	 * @param (int) ($l) Length of the generated string
	 * @return (string) Generated string
	 */
	function randomString($l)
	{
		$c= "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
		$res = "";
		srand((double)microtime()*1000000);
		for($i=0; $i<$l; $i++) $res.= $c[rand()%strlen($c)];
		return $res;
	}


	/*
	 * generateKey
	 * Generate a single beta key
	 *
	 * @return (string) the beta key
	 */
	function generateKey() {
		$dict = "0123456789abcdef";
		$t = 4;
		$key = "";
		while ($t != 0) {
			$i = 4;
			while ($i != 0) {
				$key .= $dict[rand(1,strlen($dict)) - 1];
				$i -= 1;
			}
			if ($t != 1)
				$key .= "-";
			$t -= 1;
		}
		return $key;
	}

	function getIP() {
		return getenv('REMOTE_ADDR'); // Add getenv('HTTP_FORWARDED_FOR')?: before getenv if you are using a dumb proxy. Meaning that if you try to get the user's IP with REMOTE_ADDR, it returns 127.0.0.1 or keeps saying the same IP, always.
		// NEVER add getenv('HTTP_FORWARDED_FOR') if you're not behind a proxy.
		// It can easily be spoofed.
	}




	/****************************************
	 **		HTML/PAGES   FUNCTIONS 		   **
	 ****************************************/

	/*
	 * setTitle
	 * sets the title of the current $p page.
	 *
	 * @param (int) ($p) page ID.
	 */
	function setTitle($p)
	{
		if (isset($_COOKIE["st"]) && $_COOKIE["st"] == 1)
		{
			// Safe title, so Peppy doesn't know we are browsing Ripple
			echo('<title>Google</title>');
		}
		else
		{
			// Unsafe title, show actual title
			switch($p)
			{
				case 1: echo('<title>Ripple - Custom osu! server</title>'); break;
				case 2: echo('<title>Ripple - Login</title>'); break;
				case 3: echo('<title>Ripple - Register</title>'); break;
				case 4: echo('<title>Ripple - User CP</title>'); break;
				case 5: echo('<title>Ripple - Change avatar</title>'); break;
				case 9: case 10: case 11: echo('<title>Ripple - Coming soon</title>'); break;
				case 6: echo('<title>Ripple - Edit user settings</title>'); break;
				case 7: echo('<title>Ripple - Change password</title>'); break;
				case 8: echo('<title>Ripple - Edit userpage</title>'); break;
				case 12: echo('<title>Ripple - Change osu! id</title>'); break;
				case 13: echo('<title>Ripple - Leaderboard</title>'); break;
				case 14: echo('<title>Ripple - Documentation files</title>'); break;
				case 15: echo('<title>Ripple - Read documentation</title>'); break;
				case 16: echo('<title>Ripple - Read documentation</title>'); break;
				case 17: echo('<title>Ripple - Changelog</title>'); break;
				case 18: echo('<title>Ripple - Recover your password</title>'); break;
				case 19: echo('<title>Ripple - Finish password recovery</title>'); break;
				case 20: echo('<title>Ripple - Beta keys</title>'); break;
				case 21: echo('<title>Ripple - About</title>'); break;

				case 100: echo('<title>RAP - Dashboard</title>');
				case 101: echo('<title>RAP - System settings</title>');
				case 102: echo('<title>RAP - Users</title>');
				case 103: echo('<title>RAP - Edit user</title>');
				case 104: echo('<title>RAP - Change identity</title>');
				case 105: echo('<title>RAP - Beta Keys</title>');
				case 106: echo('<title>RAP - Docs Pages</title>');
				case 107: echo('<title>RAP - Edit doc page</title>');
				case 108: echo('<title>RAP - Badges</title>');
				case 109: echo('<title>RAP - Edit Badge</title>');
				case 110: echo('<title>RAP - Edit user badges</title>');
				case 111: echo('<title>RAP - Bancho settings</title>');

				case "u": echo('<title>Ripple - Userpage</title>'); break;
				default: echo('<title>Ripple - 404</title>'); break;
			}
		}
	}


	/*
	 * printPage
	 * Prints the content of a page.
	 * For protected pages (logged in only pages), call first sessionCheck() and then print the page.
	 * For guest pages (logged out only pages), call first checkLoggedIn() and if false print the page.
	 *
	 * @param (int) ($p) page ID.
	 */
	function printPage($p)
	{
		$exceptions = array(
			"pls goshuujin-sama do not hackerino &gt;////&lt;",
			"Only administrators are allowed to see that documentation file.",
			"<div style='font-size: 40pt;'>ATTEMPTED USER ACCOUNT VIOLATION DETECTED</div>
			<p>We detected an attempt to violate an user account. If you did not this on purpose, you can ignore this message and login into your account normally. However if you changed your cookies on purpose and you were trying to access another user's account, don't do that.</p>
			<p>By the way, the attacked user is aware that you tried to get access to their account, and we removed all permanent logins hashes. We wish you good luck in even finding what's the new 's' cookie for that user.</p>
			<p>Don't even try.</p>",
			9001 => "don't even try"
		);
		if (!isset($_GET["u"]) || empty($_GET["u"]))
		{
			// Standard page
			switch($p)
			{
				// Error page
				case 99: if (isset($_GET["e"]) && isset($exceptions[$_GET["e"]])) $e = $_GET["e"]; else $e = "9001"; P::ExceptionMessage($exceptions[$e]); break;

				// Home
				case 1: P::HomePage(); break;

				// Login page (guest)
				case 2: if (!checkLoggedIn()) P::LoginPage(); else P::LoggedInAlert(); break;

				// Register page (guest)
				case 3:	if (!checkLoggedIn()) P::RegisterPage(); else P::LoggedInAlert(); break;

				// User CP page (protected)
				case 4: sessionCheck(); P::UserCPPage(); break;

				// Coming soon
				case 9: case 10: case 11: echo('<br><h1><i class="fa fa-cog fa-spin"></i>	Coming soon(ish)</h1>'); break;

				// Edit avatar (protected)
				case 5: sessionCheck(); P::ChangeAvatarPage(); break;

				// Edit userpage (protected)
				case 8: sessionCheck(); P::UserpageEditorPage(); break;

				// Edit user settings (protected)
				case 6: sessionCheck(); P::userSettingsPage(); break;

				// Change password (protected)
				case 7: sessionCheck(); P::ChangePasswordPage(); break;

				// Change osu! id (protected)
				case 12: sessionCheck(); P::SetOsuIDPage(); break;

				// Leaderboard
				case 13: sessionCheck(); P::Leaderboard(); break;

				// List documentation files
				case 14: listDocumentationFiles(); break;

				// Show documentation file (check if f is set to avoid errors and stuff)
				case 15: if (isset($_GET["f"]) && !empty($_GET["f"])) redirectToNewDocs($_GET["f"]); else redirectToNewDocs(NULL); break;

				// Show documentation, v2 with database
				case 16: if (isset($_GET["id"]) && intval($_GET["id"])) getDocPageAndParse(intval($_GET["id"])); else getDocPageAndParse(NULL); break;

				// Show changelog
				case 17: P::ChangelogPage(); break;

				// Password recovery
				case 18: P::PasswordRecovery(); break;

				// Finish password recovery
				case 19: P::PasswordFinishRecovery(); break;

				// Beta keys page
				case 20: P::BetaKeys(); break;

				// About page
				case 21: P::AboutPage(); break;

				// Admin panel (> 100 pages are admin ones)
				case 100: sessionCheckAdmin(); P::AdminDashboard(); break;

				// Admin panel - System settings
				case 101: sessionCheckAdmin(); P::AdminSystemSettings(); break;

				// Admin panel - Users
				case 102: sessionCheckAdmin(); P::AdminUsers(); break;

				// Admin panel - Edit user
				case 103: sessionCheckAdmin(); P::AdminEditUser(); break;

				// Admin panel - Change identity
				case 104: sessionCheckAdmin(); P::AdminChangeIdentity(); break;

				// Admin panel - Beta keys
				case 105: sessionCheckAdmin(); P::AdminBetaKeys(); break;

				// Admin panel - Documentation
				case 106: sessionCheckAdmin(); P::AdminDocumentation(); break;

				// Admin panel - Edit Documentation file
				case 107: sessionCheckAdmin(); P::AdminEditDocumentation(); break;

				// Admin panel - Badges
				case 108: sessionCheckAdmin(); P::AdminBadges(); break;

				// Admin panel - Edit badge
				case 109: sessionCheckAdmin(); P::AdminEditBadge(); break;

				// Admin panel - Edit uesr badges
				case 110: sessionCheckAdmin(); P::AdminEditUserBadges(); break;

				// Admin panel - System settings
				case 111: sessionCheckAdmin(); P::AdminBanchoSettings(); break;

				// 404 page
				default: echo('<br><h1>404</h1><p>Page not found. Meh.</p>'); break;
			}
		}
		else
		{
			// Userpage

			// Protected page
			sessionCheck();

			// Get playmode (default 0)
			if (!isset($_GET["m"]) || empty($_GET["m"]))
				$m = 0;
			else
				$m = $_GET["m"];

			// Print userpage
			P::UserPage($_GET["u"], $m);
		}
	}


	/*
	 * printNavbar
	 * Prints the navbar.
	 * To print tabs only for guests (not logged in), do
	 *	if (!checkLoggedIn()) echo('stuff');
	 *
	 * To print tabs only for logged in users, do
	 *	if (checkLoggedIn()) echo('stuff');
	 *
	 * To print tabs for both guests and logged in users, do
	 *	echo('stuff');
	 */
	function printNavbar()
	{
		// Navbar start
		echo('<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
			<div class="container">
				<div class="navbar-header">
					<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
						<span class="sr-only">Toggle navigation</span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>
					<a class="navbar-brand" href="index.php">Ripple</a>
				</div>
				<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
					<ul class="nav navbar-nav">');

		// Navbar menu, add menu stuff here
		if (!checkLoggedIn()) echo('<li><a href="index.php?p=2"><i class="fa fa-sign-in"></i>	Login</a></li>');
		if (!checkLoggedIn()) echo('<li><a href="index.php?p=3"><i class="fa fa-plus-circle"></i>	Sign up</a></li>');
		if (!checkLoggedIn()) echo('<li><a href="index.php?p=21"><i class="fa fa-info"></i>	About</a></li>');
		if (checkLoggedIn()) echo('<li><a href="index.php?p=13"><i class="fa fa-trophy"></i>	Leaderboard</a></li>');
		if (checkLoggedIn()) echo('<li><a href="index.php?p=4"><i class="fa fa-user"></i>	User Panel</a></li>');
		echo('<li><a href="http://bloodcat.com/osu/"><i class="fa fa-music"></i>	Beatmaps</a></li>');
		if (checkLoggedIn()) echo('<li><a href="index.php?p=17"><i class="fa fa-bug"></i>	Changelog</a></li>');
		echo('<li><a href="index.php?p=14"><i class="fa fa-question-circle"></i>	Help</a></li>');
		if (!checkLoggedIn()) echo('<li><a href="index.php?p=20"><i class="fa fa-key"></i>	Beta keys</a></li>');
		if (checkLoggedIn() && getUserRank($_SESSION["username"]) >= 4) echo('<li><a href="index.php?p=100"><i class="fa fa-cog"></i>	<b>Admin Panel</b></a></li>');
		if (checkLoggedIn()) echo('<li><a href="submit.php?action=logout"><i class="fa fa-sign-out"></i>	<b>Logout</b></a></li>');

		// Navbar end
		echo('</ul></div></div></nav>');
	}


	/*
	 * printAdminSidebar
	 * Prints the admin left sidebar
	 */
	function printAdminSidebar()
	{
		echo('<div id="sidebar-wrapper">
					<ul class="sidebar-nav">
						<li class="sidebar-brand">
							<a href="#">
								<b>R</b>ipple <b>A</b>dmin <b>P</b>anel
							</a>
						</li>
						<li>
							<a href="index.php?p=100"><i class="fa fa-tachometer"></i>	Dashboard</a>
						</li>
						<li>
							<a href="index.php?p=101"><i class="fa fa-cog"></i>	System settings</a>
						</li>
						<li>
							<a href="index.php?p=111"><i class="fa fa-server"></i>	Bancho settings</a>
						</li>
						<li>
							<a href="index.php?p=102"><i class="fa fa-user"></i>	Users</a>
						</li>
						<li>
							<a href="index.php?p=108"><i class="fa fa-certificate"></i>	Badges</a>
						</li>
						<li>
							<a href="#"><i class="fa fa-gamepad"></i><s>	Scores</s></a>
						</li>
						<li>
							<a href="#"><i class="fa fa-music"></i>	<s>Beatmaps</s></a>
						</li>
						<li>
							<a href="index.php?p=105"><i class="fa fa-gift"></i>	Beta keys</a>
						</li>
						<li>
							<a href="index.php?p=106"><i class="fa fa-question-circle"></i>	Documentation</a>
						</li>
						<li>
							<a href="#"><i class="fa fa-info-circle"></i>	<s>Info</s></a>
						</li>
					</ul>
				</div>');
	}


	/*
	 * printAdminPanel
	 * Prints an admin dashboard panel, used to show
	 * statistics (like total plays, beta keys left and stuff)
	 *
	 * @c (string) panel color, you can use standard bootstrap colors or custom ones (add them in style.css)
	 * @i (string) font awesome icon of that panel. Recommended doing fa-5x (Eg: fa fa-gamepad fa-5x)
	 * @bt (string) big text, usually the value
	 * @st (string) small text, usually the name of that stat
	 */
	function printAdminPanel($c, $i, $bt, $st)
	{
		echo('<div class="col-lg-3 col-md-6">
			<div class="panel panel-'.$c.'">
			<div class="panel-heading">
			<div class="row">
			<div class="col-xs-3"><i class="'.$i.'"></i></div>
			<div class="col-xs-9 text-right">
				<div class="huge">'.$bt.'</div>
				<div>'.$st.'</div>
			</div></div></div></div></div>');
	}

	/*
	* getUserCountry
	* Does a call to ip.zxq.co to get the user's IP address.
	*
	* @returns (string) A 2-character string containing the user's country.
	*/
	function getUserCountry() {
		$ip = getIP();
		if (!$ip) {
			return "XX"; // Return XX if $ip isn't valid.
		}
		// otherwise, retrieve the contents from ip.zxq.co's API
		$data = get_contents_http("http://ip.zxq.co/$ip/country");
		// And return the country. If it's set, that is.
		return ($data != "" ? $data : "XX");
	}

	function countryCodeToReadable($cc) {
		require_once dirname(__FILE__) . "/countryCodesReadable.php";
		return (isset($c[$cc]) ? $c[$cc] : "unknown country");
	}

	/*
	* getAllowedUsers()
	* Get an associative array, saying whether a user is banned or not on ripple.
	*
	* @returns (array) see above.
	*/
	function getAllowedUsers($by = "username") {
		// get all the allowed users in ripple
		$allowedUsersRaw = $GLOBALS["db"]->fetchAll("SELECT " . $by . ", allowed FROM users");
		// Future array containing all the allowed users.
		$allowedUsers = array();
		// Fill up the $allowedUsers array.
		foreach ($allowedUsersRaw as $u) {
			$allowedUsers[$u[$by]] = ($u["allowed"] == '1' ? true : false);
		}
		// Free up some space in the ram by deleting the data in $allowedUsersRaw.
		unset($allowedUsersRaw);
		return $allowedUsers;
	}


	/****************************************
	 **	 LOGIN/LOGOUT/SESSION FUNCTIONS	   **
	 ****************************************/

	/*
	 * startSessionIfNotStarted
	 * Starts a session only if not started yet.
	 */
	function startSessionIfNotStarted()
	{
		if (session_status() == PHP_SESSION_NONE) session_start();
	}


	/*
	 * sessionCheck
	 * Check if we are logged in, otherwise go to login page.
	 * Used for logged-in only pages
	 */
	function sessionCheck()
	{
		try
		{
			// Start session
			startSessionIfNotStarted();

			// Check if we are logged in
			if (!$_SESSION) {
				// Check for the autologin cookies.
				$c = new RememberCookieHandler();
				if ($c->Check()) {
					if ($c->Validate() === 0) {
						throw new Exception(3);
					}
					// We don't need to handle any other case.
					// If it's -1, alert will automatically be triggered and user sent to error page.
					// If it's -2, same as above.
					// If it's 1, this function will keep on executing normally.
				}
				else {
					throw new Exception(3);
				}
			}

			// Check if we've changed our password
			if ($_SESSION["passwordChanged"])
			{
				// Update our session password so we don't get kicked
				$_SESSION["password"] = current($GLOBALS["db"]->fetch("SELECT password_secure FROM users WHERE username = ?", $_SESSION["username"]));

				// Reset passwordChanged
				$_SESSION["passwordChanged"] = false;
			}

			// Check if our password is still valid
			if (current($GLOBALS["db"]->fetch("SELECT password_secure FROM users WHERE username = ?", $_SESSION["username"])) != $_SESSION["password"]) {
				throw new Exception(4);
			}

			// Check if we aren't banned
			if (current($GLOBALS["db"]->fetch("SELECT allowed FROM users WHERE username = ?", $_SESSION["username"])) == 0) {
				throw new Exception(2);
			}

			// Everything is ok, go on
		}
		catch (Exception $e)
		{
			// Destroy session if it still exists
			D::Logout();

			// Return to login page
			redirect("index.php?p=2&e=".$e->getMessage());
		}
	}


	/*
	 * sessionCheckAdmin
	 * Check if we are logged in, and we are admin.
	 * Used for admin pages (like admin cp)
	 * Call this function instead of sessionCheck();
	 */
	function sessionCheckAdmin($e = 0)
	{
		sessionCheck();
		if (getUserRank($_SESSION["username"]) < 4) {
			redirect("index.php?p=99&e=" . $e);
			return false;
		}
		else {
			return true;
		}
	}


	/*
	 * updateLatestActivity
	 * Updates the latest_activity column for $u user
	 *
	 * @param ($u) (string) Username
	 */
	function updateLatestActivity($u)
	{
		$GLOBALS["db"]->execute("UPDATE users SET latest_activity = ? WHERE username = ?", array(time(), $u));
	}


	/*
	 * updateSafeTitle
	 * Updates the st cookie, if 1 title is "Google" instead
	 * of Ripple - pagename, so Peppy doesn't know that
	 * we are browsing ripple
	 */
	function updateSafeTitle()
	{
		$safeTitle = $GLOBALS["db"]->fetch("SELECT safe_title FROM users_stats WHERE username = ?", $_SESSION["username"]);
		setcookie("st", current($safeTitle));
	}


	/*
	 * timeDifference
	 * Returns a string with difference from $t1 and $t2
	 *
	 * @param (int) ($t1) Current time. Usually time()
	 * @param (int) ($t2) Event time.
	 * @return (string) A string in "x minutes/hours/days ago" format
	 */
	function timeDifference($t1, $t2)
	{
		// Calculate difference in seconds
		// abs and +1 should fix memes
		$d=abs($t1-$t2+1);

		switch ($d)
		{
			// Right now
			default: return "Right now"; break;

			// 1 year or more
			case ($d >= 31556926): $n = floor($d/31556926); $i = "year"; break;

			// 1 month or more
			case ($d >= 2629743 && $d < 31556926): $n = floor($d/2629743); $i = "month"; break;

			// 1 day or more
			case ($d >= 86400 && $d < 2629743): $n = floor($d/86400); $i = "day"; break;

			// 1 hour or more
			case ($d >= 3600 && $d < 86400): $n = floor($d/3600); $i = "hour"; break;

			// 1 minute or more
			case ($d >= 60 && $d < 3600): $n = floor($d/60); $i = "minute"; break;
		}

		// Plural
		if ($n > 1) $s = "s"; else $s = "";

		return $n." ".$i.$s." ago";
	}


	$checkLoggedInCache = -100;
	/*
	 * checkLoggedIn
	 * Similar to sessionCheck(), but let the user choose what to do if logged in or not
	 *
	 * @return (bool) true: logged in / false: not logged in
	 */
	function checkLoggedIn()
	{
		global $checkLoggedInCache;
		// Start session
		startSessionIfNotStarted();

		if ($checkLoggedInCache !== -100) {
			return $checkLoggedInCache;
		}

		// Check if we are logged in
		if (!$_SESSION) {
			// Check for the autologin cookies.
			$c = new RememberCookieHandler();
			if ($c->Check()) {
				if ($c->Validate() === 0) {
					$checkLoggedInCache = false;
					return false;
				}
				// We don't need to handle any other case.
				// If it's -1, alert will automatically be triggered and user sent to error page.
				// If it's -2, same as above.
				// If it's 1, this function will keep on executing normally.
			}
			else {
				$checkLoggedInCache = false;
				return false;
			}
		}

		// Check if our password is still valid
		if ($GLOBALS["db"]->fetch("SELECT password FROM users WHERE username = ?", $_SESSION["username"]) == $_SESSION["password"]) {
			$checkLoggedInCache = false;
			return false;
		}

		// Check if we aren't banned
		if ($GLOBALS["db"]->fetch("SELECT allowed FROM users WHERE username = ?", $_SESSION["username"]) == 0) {
			$checkLoggedInCache = false;
			return false;
		}

		// Everything is ok, go on
		$checkLoggedInCache = true;
		return true;
	}


	/*
	 * getUserAllowed
	 * Gets the allowed status of the $u user
	 *
	 * @return (int) allowed (1: ok, 2: not active yet (own check thing), 0: banned)
	 */
	function getUserAllowed($u)
	{
		return current($GLOBALS["db"]->fetch("SELECT allowed FROM users WHERE username = ?", $u));
	}


	/*
	 * getUserRank
	 * Gets the rank of the $u user
	 *
	 * @return (int) rank
	 */
	function getUserRank($u)
	{
		return current($GLOBALS["db"]->fetch("SELECT rank FROM users WHERE username = ?", $u));
	}

	function checkWebsiteMaintenance()
	{
		if (current($GLOBALS["db"]->fetch("SELECT value_int FROM system_settings WHERE name = 'website_maintenance'")) == 0) return false; else return true;
	}

	function checkGameMaintenance()
	{
		if (current($GLOBALS["db"]->fetch("SELECT value_int FROM system_settings WHERE name = 'game_maintenance'")) == 0) return false; else return true;
	}

	function checkBanchoMaintenance()
	{
		if (current($GLOBALS["db"]->fetch("SELECT value_int FROM bancho_settings WHERE name = 'bancho_maintenance'")) == 0) return false; else return true;
	}

	function checkRegistrationsEnabled()
	{
		if (current($GLOBALS["db"]->fetch("SELECT value_int FROM system_settings WHERE name = 'registrations_enabled'")) == 0) return false; else return true;
	}





	/****************************************
	 **	  DOCUMENTATION FUNCTIONS	   **
	 ****************************************/

	/*
	 * listDocumentationFiles
	 * Retrieves all teh files in the folder ../docs/,
	 * parses their filenames and then returns them in alphabetical order.
	 */
	function listDocumentationFiles() {
		// Maintenance alerts
		P::MaintenanceStuff();

		// Global alert
		P::GlobalAlert();

		echo '<div id="narrow-content"><h1><i class="fa fa-question-circle"></i> Ripple documentation</h1>';
		$e = "<ul class='text-left'>\n";
		$data = $GLOBALS["db"]->fetchAll("SELECT id, doc_name FROM docs WHERE public = '1'");
		if (count($data) != 0) {
			foreach ($data as $value) {
				$e .= "<li><a href='index.php?p=16&id=" . $value["id"] . "'>"
					. $value["doc_name"] . "</a></li>\n";
			}
		}
		else {
			$e .= "It looks like there are no documentation files! Perhaps try again later?";
		}
		$e .= "</ul>";
		echo $e;
		echo("</div>");
	}


	/*
	 * redirectToNewDocs
	 * Redirects the user to the new documentation file's place.
	 *
	 * @param (string) ($docname) The documentation file name.
	 */
	function redirectToNewDocs($docname) {
		$new = $GLOBALS["db"]->fetch("SELECT id FROM docs WHERE old_name = ?;", array($docname));
		if ($new == false) {
			redirect("index.php?p=16&id=9001");
		}
		else {
			redirect("index.php?p=16&id=" . $new["id"]);
		}
	}

	/*
	 * getDocPageAndParse
	 * Gets a page on the documentation.
	 *
	 * @param (string) ($docid) The document ID.
	 */
	function getDocPageAndParse($docid) {
		// Maintenance check
		P::MaintenanceStuff();

		// Global alert
		P::GlobalAlert();

		try {
			if ($docid === null) {
				throw new Exception();
			}
			$doc = $GLOBALS["db"]->fetch("SELECT doc_contents, public FROM docs WHERE id = ?;", $docid);
			if ($doc["public"] == "0" && !sessionCheckAdmin(1)) {
				return;
			}
			if ($doc == false) {
				throw new Exception();
			}
			require_once "parsedown.php";
			$p = new Parsedown();
			echo "<div class='text-left'>" .
				$p->text($doc["doc_contents"])
				. "</div>";
		}
		catch (Exception $e) {
			echo "<br>That documentation file could not be found!";
		}
	}




	/****************************************
	 **	 	 GENERAL  OSU  FUNCTIONS   	   **
	 ****************************************/

	/*
	 * checkOsuUser
	 * Check if a user exists and if his password is correct
	 * Used for osu stuff (uses MD5 password)
	 *
	 * @param (string) ($u) Username
	 * @param (string) ($p) MD5 Password
	 * @return (bool)
	 */
	function checkOsuUser($u, $p)
	{
		try
		{
			// Check if everything is set
			if (!isset($u) || !isset($p) || empty($u) || empty($p)) {
				throw new Exception;
			}

			// Check username/password
			if (!$GLOBALS["db"]->fetch("SELECT id FROM users WHERE username = ? AND password_md5 = ?", array($u, $p))) {
				throw new Exception;
			}

			// Everything ok, return true
			return true;
		}
		catch (Exception $e)
		{
			return false;
		}
	}


	$cachedOsuID = false;
	/*
	 * getUserOsuID
	 * Get the osu! user ID of the $u user
	 *
	 * @param (string) ($u) Username
	 * @return (string) osu! id of $u
	 */
	function getUserOsuID($u)
	{
		global $cachedOsuID;
		if (isset($cachedOsuID[$u]))
			return $cachedOsuID[$u];
		$osuID = $GLOBALS["db"]->fetch("SELECT osu_id FROM users WHERE username = ?", $u);
		if ($osuID)
		{
			// Osu! ID returned. If 2 (Default) return 0 (Not set).
			if ($osuID != 2)
				$cachedOsuID[$u] = current($osuID);
			else
				$cachedOsuID[$u] = 0;
		}
		else
		{
			// Osu! ID not set, maybe invlid player. Return 0.
			$cachedOsuID[$u] = 0;
		}
		return $cachedOsuID[$u];
	}


	/*
	 * getPlaymodeText
	 * Returns a text representation of a playmode integer.
	 *
	 * @param (int) ($playModeInt) an integer from 0 to 3 (inclusive) stating the play mode.
	 * @param (bool) ($readable) set to false for returning values to be inserted into the db. set to true for having something human readable (osu!standard / Taiko...)
	 */
	function getPlaymodeText($playModeInt, $readable = false) {
		switch ($playModeInt) {
			case 1: return ($readable ? "Taiko" : "taiko"); break;
			case 2: return ($readable ? "Catch the Beat" : "ctb"); break;
			case 3: return ($readable ? "osu!mania" : "mania"); break;
			// Protection against memes from the users
			default: return ($readable ? "osu!standard" : "std"); break;
		}
	}



	/****************************************
	 **		 SUBMIT MODULAR FUNCTIONS 	   **
	 ****************************************/

	/*
	 * saveScore
	 * Save a score in db
	 *
	 * @param (array) ($scoreDataArray) Score data array (exploded string)
	 * @param (int)   ($completed) Value of completed. 0: Failed, 1: Retried, 2: Completed but no best score, 3: Best score. Default is 2. Optional.
	 * @param (bool)  ($saveScore) Save this score in DB. Default is true. Optional.
	 * @param (bool)  ($increasePlaycount) Increase playcount of score user. Default is true. Optional.
	 * @return (int) Play ID int, used to store replayID (which is the same as play ID)
	 */
	function saveScore($scoreDataArray, $completed = 2, $saveScore = true, $increasePlaycount = true)
	{
		// Save exploded string into human readable vars
		$beatmapHash 	= $scoreDataArray[0];
		$username 		= $scoreDataArray[1];
		//$??	 		= $scoreDataArray[2];
		$count300 		= $scoreDataArray[3];
		$count100 		= $scoreDataArray[4];
		$count50 		= $scoreDataArray[5];
		$countGeki 		= $scoreDataArray[6];
		$countKatu 		= $scoreDataArray[7];
		$countMisses	= $scoreDataArray[8];
		$score 			= $scoreDataArray[9];
		$maxCombo		= $scoreDataArray[10];
		$fullCombo		= $scoreDataArray[11];
		$rank			= $scoreDataArray[12];
		$mods			= $scoreDataArray[13];
		$passed			= $scoreDataArray[14];
		$playMode		= $scoreDataArray[15];
		$playDateTime	= $scoreDataArray[16];
		$osuVersion		= $scoreDataArray[17];
		$playModeText = getPlaymodeText($playMode);

		// Update country flag
		updateCountryIfNeeded($username);

		// Update latest activity
		updateLatestActivity($username);

		// If we have played with some unranked mods, let's not save the score.
		if (!isRankable($mods)) {
			if ($increasePlaycount)
				increasePlaycountAndScore($playModeText, $score, $username);
			return 0;
		}

		// We have finished a song
		if ($completed == 2)
		{
			// We've finished a song
			// Get our best play for this beatmap
			$topScore = $GLOBALS["db"]->fetch("SELECT * FROM scores WHERE beatmap_md5 = ? AND username = ? AND completed = 3", array($beatmapHash, $username) );
			if ($topScore)
			{
				// We have a top score on this map, so it's not a first play.
				// Check if the score that we are submitting is better than our top one
				if ($score > $topScore["score"])
				{
					// New best score!
					$completed = 3;

					// Get difference (so we add only the right amount of score to total score)
					$scoreDifference = $score - $topScore["score"];

					// Change old best score to normal completed score
					$GLOBALS["db"]->execute("UPDATE scores SET completed = 2 WHERE id = ?", $topScore["id"]);
				}
				else
				{
					// No new best score :(
					$completed = 2;

					// Since we've made a worse score, we add nothing to our total score
					$scoreDifference = 0;
				}
			}
			else
			{
				// This is the first time that we play and finish this map, so it's a top score
				$completed = 3;

				// Score difference is equal to current score because this is our first play
				$scoreDifference = $score;
			}

			// Do total score + score difference (on our play mode) if we have a new best
			if ($completed == 3)
			{
				// Update ranked score
				$GLOBALS["db"]->execute("UPDATE users_stats SET ranked_score_" . $playModeText . "=ranked_score_" . $playModeText . "+? WHERE username = ?", array($scoreDifference, $username));
			}
		}

		if ($increasePlaycount)
			increasePlaycountAndScore($playModeText, $score, $username);

		// Add score in db if we want it
		if ($saveScore)
		{
			$acc = strval(calculateAccuracy($count300, $count100, $count50, $countGeki, $countKatu, $countMisses, $playMode));
			$GLOBALS["db"]->execute("INSERT INTO scores (id, beatmap_md5, username, score, max_combo, full_combo, mods, 300_count, 100_count, 50_count, katus_count, gekis_count, misses_count, time, play_mode, completed, accuracy) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);", array($beatmapHash, $username, $score, $maxCombo, $fullCombo, $mods, $count300, $count100, $count50, $countKatu, $countGeki, $countMisses, $playDateTime, $playMode, $completed, $acc));
			$r = $GLOBALS["db"]->lastInsertId();
			updateAccuracy($username, $playMode);
			return $r;
		}

		// Return 0 if we haven't submitted any score
		return 0;
	}


	/*
	 * saveReplays
	 * Save score replay in replays folder
	 *
	 * @return (bool) True if ok, otherwise return false
	 */
	function saveReplay($replayID)
	{
		// Check if replay is provided
		if ($_FILES)
		{
			// Check size
			if ($_FILES["score"]["size"] > 0)
			{
				// Upload compressed replay (replay is decompressed on osu! client)
				move_uploaded_file($_FILES["score"]["tmp_name"], "../replays/replay_".$replayID.".osr");

				// Ok
				return true;
			}
		}
		else
		{
			// Replay not provided
			return false;
		}
	}

	function increasePlaycountAndScore($playMode, $score, $username) {
		// Increase playcount and score.
		$GLOBALS["db"]->execute("UPDATE users_stats SET playcount_" . $playMode . "=playcount_" . $playMode . "+1, total_score_" . $playMode . "=total_score_" . $playMode . "+? WHERE username = ?", array($score, $username));
		// As we are increasing the total score, we are also updating the level.
		$totalScore = $GLOBALS["db"]->fetch("SELECT total_score_" . $playMode . " FROM users_stats WHERE username = ?", $username);
		$level = getLevel($totalScore["total_score_" . $playMode]);
		$GLOBALS["db"]->execute("UPDATE users_stats SET level_" . $playMode . " = ? WHERE username = ?", array($level, $username));
	}


	/*
	 * getScoreMods
	 * Gets the mods for the $m mod flag
	 *
	 * @param (int) ($m) Mod flag
	 * @returns (string) Eg: "+ HD, HR"
	 */
	function getScoreMods($m)
	{
		require_once dirname(__FILE__) . "/ModsEnum.php";
		$r = "";
		if ($m & ModsEnum::NoFail) $r .= "NF, ";
		if ($m & ModsEnum::Easy) $r .= "EZ, ";
		if ($m & ModsEnum::NoVideo) $r .= "NV, ";
		if ($m & ModsEnum::Hidden) $r .= "HD, ";
		if ($m & ModsEnum::HardRock) $r .= "HR, ";
		if ($m & ModsEnum::SuddenDeath) $r .= "SD, ";
		if ($m & ModsEnum::DoubleTime) $r .= "DT, ";
		if ($m & ModsEnum::Relax) $r .= "RX, ";
		if ($m & ModsEnum::HalfTime) $r .= "HT, ";
		if ($m & ModsEnum::Nightcore) $r .= "NC, "; $r = str_replace("DT, ", "", $r);	// Remove DT and display only NC
		if ($m & ModsEnum::Flashlight) $r .= "FL, ";
		if ($m & ModsEnum::Autoplay) $r .= "AP, ";
		if ($m & ModsEnum::SpunOut) $r .= "SO, ";
		if ($m & ModsEnum::Relax2) $r .= "AP, ";
		if ($m & ModsEnum::Perfect) $r .= "PF, ";
		if ($m & ModsEnum::Key4) $r .= "4K, ";
		if ($m & ModsEnum::Key5) $r .= "5K, ";
		if ($m & ModsEnum::Key6) $r .= "6K, ";
		if ($m & ModsEnum::Key7) $r .= "7K, ";
		if ($m & ModsEnum::Key8) $r .= "8K, ";
		if ($m & ModsEnum::keyMod) $r .= "";
		if ($m & ModsEnum::FadeIn) $r .= "FD, ";
		if ($m & ModsEnum::Random) $r .= "RD, ";
		if ($m & ModsEnum::LastMod) $r .= "CN, ";
		if ($m & ModsEnum::Key9) $r .= "9K, ";
		if ($m & ModsEnum::Key10) $r .= "10K, ";
		if ($m & ModsEnum::Key1) $r .= "1K, ";
		if ($m & ModsEnum::Key3) $r .= "3K, ";
		if ($m & ModsEnum::Key2) $r .= "2K, ";

		// Add "+" and remove last ", "
		if (strlen($r) > 0) return "+ ".substr($r, 0, -2); else return "";
	}


	/*
	 * isRankable
	 * Check if a set of mods is rankable (doesn't have Relax or Autopilot).
	 *
	 * @param (string) ($mod) A ModsEnum containing the mods of a play.
	 * @returns (bool) true in case the mods are rankable, false otherwise.
	 */
	function isRankable($mod) {
		require_once dirname(__FILE__) . "/ModsEnum.php";
		if ($mod & ModsEnum::Relax || $mod & ModsEnum::Relax2
			|| $mod & ModsEnum::Autoplay)
			return false;
		else
			return true;
	}

	/*
	 * updateCountryIfNeeded
	 * Updates the user's country in the database if the country is still XX.
	 *
	 * @param (string) ($username) The username of the user. What a dumb explaination.
	 */
	function updateCountryIfNeeded($username) {
		// If we're doing stuff from localhost, don't even try.
		if (getIP() == "127.0.0.1")
			return;
		$userCountry = $GLOBALS["db"]->fetch("SELECT country FROM users_stats WHERE username = ?;", $username);
		if ($userCountry === false) {
			return;
		}
		if ($userCountry["country"] == "XX") {
			$actualUserCountry = getUserCountry();
			if ($actualUserCountry != "XX")
				$GLOBALS["db"]->execute("UPDATE users_stats SET country=? WHERE username = ?;", array($actualUserCountry, $username));
		}
	}

	/*
	 * updateAccuracy
	 * Updates an user's accuracy in the database.
	 *
	 * @param string $username The username.
	 */
	function updateAccuracy($username, $playMode) {
		$playModeText = getPlaymodeText($playMode);
		// get best accuracy scores
		$a = $GLOBALS["db"]->fetchAll("SELECT accuracy FROM scores WHERE username = ? AND play_mode = ? AND completed = '3' ORDER BY accuracy DESC LIMIT 100;", array($username, $playMode));
		// calculate weighted accuracy
		$totalacc = 0;
		$divideTotal = 0;
		foreach ($a as $k => $p) {
			$add = intval(pow(0.95, $k) * 100);
			$totalacc += $p["accuracy"] * $add;
			$divideTotal += $add;
			//echo "$add - $totalacc - $divideTotal\n";
		}
		if ($divideTotal !== 0)
			$v = ($totalacc / $divideTotal);
		else
			$v = 0;
		$GLOBALS["db"]->execute("UPDATE users_stats SET avg_accuracy_" . $playModeText . " = ? WHERE username = ?", array($v, $username));
	}

	/*
	 * calculateAccuracy
	 * Calculates the accuracy of a score in a given gamemode.
	 *
	 * @param int $n300 The number of 300 hits in a song.
	 * @param int $n100 The number of 100 hits in a song.
	 * @param int $n50 The number of 50 hits in a song.
	 * @param int $ngeki The number of geki hits in a song.
	 * @param int $nkatu The number of katu hits in a song.
	 * @param int $nmiss The number of missed hits in a song.
	 * @param int $mode The game mode.
	 */
	function calculateAccuracy($n300, $n100, $n50, $ngeki, $nkatu, $nmiss, $mode) {
		// For reference, see: http://osu.ppy.sh/wiki/Accuracy
		switch ($mode) {
			case 0:
				$totalPoints = ($n50 * 50 + $n100 * 100 + $n300 * 300);
				$maxHits = ($nmiss + $n50 + $n100 + $n300);
				$accuracy = $totalPoints / ($maxHits * 300);
				break;
			case 1:
				// Please note this is not what is written on the wiki.
				// However, what was written on the wiki didn't make any sense at all.
				$totalPoints = ($n100 * 50 + $n300 * 100);
				$maxHits = ($nmiss + $n100 + $n300);
				$accuracy = $totalPoints / ($maxHits * 100);
				break;
			case 2:
				$fruits = $n300 + $n100 + $n50;
				$totalFruits = $fruits + $nmiss + $nkatu;
				$accuracy = $fruits / $totalFruits;
				break;
			case 3:
				$totalPoints = ($n50 * 50 + $n100 * 100 + $nkatu * 200 + $n300 * 300 + $ngeki * 300);
				$maxHits = ($nmiss + $n50 + $n100 + $n300 + $ngeki + $nkatu);
				$accuracy = $totalPoints / ($maxHits * 300);
				break;
		}
		return $accuracy * 100; // we're doing * 100 because $accuracy is like 0.9823[...]
	}




	/****************************************
	 **		   GETSCORES FUNCTIONS  	   **
	 ****************************************/

	/*
	 * getBeatmapRankedStatus
	 * Return ranked status of a beatmap
	 *
	 * @param (string) ($bf) Beatmap file name.
	 * @param (string) ($bmd5) Beatmap MD5.
	 * @param (bool) ($everythingIsRanked) If true, always return array(2) (aka ranked).
	 * @return (array) Array with beatmap ranked status.
	 */
	function getBeatmapRankedStatus($bf, $bmd5, $everythingIsRanked)
	{
		if ($everythingIsRanked)
		{
			// Everything is ranked, return 1
			// (we do array because we are faking a query result)
			return array(1);
		}
		else
		{
			// Return real ranked status from db
			return $GLOBALS["db"]->fetch("SELECT ranked FROM beatmaps WHERE beatmap_file = ? AND beatmap_md5 = ?", array($bf, $bmd5));
		}
	}


	/*
	 * compareBeatmapMd5
	 * Return ranked status of a beatmap
	 *
	 * @param (string) ($dbfn) Beatmap file name.
	 * @param (string) ($clmd5) Provided beatmap md5.
	 * @param (bool) ($everythingIsRanked) If true, always return $clmd5, so every beatmap is always up to date.
	 * @return (bool) True if provided md5 matches with db's one, otherwise false
	 */
	function compareBeatmapMd5($dbfn, $clmd5, $everythingIsRanked)
	{
		// Check if everything is ranked
		if ($everythingIsRanked)
		{
			// Everything is ranked, md5 is always right so return the client's one
			return $clmd5;
		}

		// Not everything is ranked, get latest beatmap md5 from file name
		$dbmd5 = $GLOBALS["db"]->fetch("SELECT beatmap_md5 FROM beatmaps WHERE beatmap_file = ?", $dbfn);

		// Check if query returned something
		if ($dbmd5)
		{
			// Query returned md5, compare client md5 with server one
			if ($clmd5 == current($dbmd5))
				return true;
			else
				return false;
		}
		else
		{
			// Query returned nothing, beatmap not in db. Return false.
			return false;
		}
	}


	/*
	 * printBeatmapHeader
	 * Print the first line of getscores. (ranked status, beatmap id and total scores)
	 *
	 * @param (int) ($s) Ranked status (-1: Not submitted, 0: Not ranked, 1: Not updated, 2: Ranked).
	 * @param (string) ($bmd5) Beatmap MD5.
	 */
	function printBeatmapHeader($s, $bmd5 = NULL)
	{
		// Print first line of score stuff
		echo($s."|false");

		// If beatmap is submitted, add other stuff
		if ($s != -1)
		{
			// Get beatmap ID (used only for beatmap forum link thing)
			$bid = $GLOBALS["db"]->fetch("SELECT beatmap_id FROM beatmaps WHERE beatmap_md5 = ?", $bmd5);

			// Check if query doesn't return any error
			if ($bid)
				$bid = current($bid);	// Set actual value
			else
				$bid = $_GET["i"];				// No result, disable forum button thing

			// Get total scores on map, count from db if ranked, otherwise is 0
			if ($s == 2)
				$tots = current($GLOBALS["db"]->fetch("SELECT COUNT(DISTINCT username) AS id FROM scores WHERE beatmap_md5 = ? AND completed = 3", $bmd5));
			else
				$tots = 0;

			// Output everything else
			echo("|".$bid."|".$bid."|".$tots."\r\n");
		}
	}


	/*
	 * printBeatmapSongInfo
	 * Print the third line of getscores. (artist and song title)
	 * It's kinda useless, but leaderboard doesn't work without this line
	 *
	 * @param (string) ($bmd5) Beatmap MD5.
	 */
	function printBeatmapSongInfo($bmd5)
	{
		// Get song artist and title from db
		$songArtist = $GLOBALS["db"]->fetch("SELECT song_artist FROM beatmaps WHERE beatmap_md5 = ?", $bmd5);
		$songTitle = $GLOBALS["db"]->fetch("SELECT song_title FROM beatmaps WHERE beatmap_md5 = ?", $bmd5);

		// Check if song data is in db
		if (!$songArtist || !$songTitle)
		{
			// Not in db, set random stuff
			$songArtist = array("Darude");
			$songTitle = array("Sandstorm");
		}

		// Echo song data
		echo("[bold:0,size:10]".current($songArtist)."|".current($songTitle)."\r\n");
	}


	/*
	 * printBeatmapSongInfo
	 * Print the fourth line of getscores. (beatmap appreciation)
	 * Not implemented yet.
	 */
	function printBeatmapAppreciation()
	{
		// Not implemented yet, output 0
		echo("\r\n");
	}


	/*
	 * printBeatmapPlayerScore
	 * Print personal score of $u user on $bmd5 beatmap (the bottom one).
	 *
	 * @param (string) ($u) Username.
	 * @param (string) ($bmd5) Beatmap MD5.
	 * @param (string) ($mode) Play mode.
	 */
	function printBeatmapPlayerScore($u, $bmd5, $mode)
	{
		// Get play id
		$pid = $GLOBALS["db"]->fetch("SELECT id FROM scores WHERE username = ? AND beatmap_md5 = ? AND play_mode = ? AND completed = 3 ORDER BY score DESC LIMIT 1", array($u, $bmd5, $mode));

		if ($pid)
		{
			// Player has already played that beatmap, print score data
			printBeatmapScore(current($pid), $bmd5, $mode);
		}
		else
		{
			// Player has not played that beatmap yet, print empty line
			echo("\r\n");
		}
	}


	/*
	 * printBeatmapTopScores
	 * Print top 50 scores of $bmd5 beatmap.
	 *
	 * @param (string) ($bmd5) Beatmap MD5.
	 * @param (int) ($mode) Playmode.
	 */
	function printBeatmapTopScores($bmd5, $mode)
	{
		// Get top 50 scores of this beatmap
		$pid = $GLOBALS["db"]->fetchAll("SELECT * FROM scores WHERE beatmap_md5 = ? AND completed = 3 AND play_mode = ? ORDER BY score DESC LIMIT 50", array($bmd5, $mode));
		$su = array();	// Users already in the leaderboard (because we show only the best score)
		$r = 1;			// Last rank (we start from #1)

		for ($i=0; $i < count($pid); $i++)
		{
			// Loop through all scores and print them based on play id
			// Check if we haven't another score by this user in the leaderboard
			if (!in_array($pid[$i]["username"], $su))
			{
				// New user, check if banned
				if (current($GLOBALS["db"]->fetch("SELECT allowed FROM users WHERE username = ?", $pid[$i]["username"])) != 0)
				{
					// Not banned, show score
					printBeatmapScore($pid[$i]["id"], $bmd5, $mode, $r);

					// Increment rank
					$r++;
				}

				// Add current user to array, so we don't add his lower scores
				array_push($su, $pid[$i]["username"]);
			}
		}
	}


	/*
	 * printBeatmapScore
	 * Return score data of $pid play.
	 *
	 * @param (int) ($pid) Play ID (first column of scores table).
	 * @param (string) ($bmd5) Beatmap MD5. Used for rank calculation when $r is not set. Optional.
	 * @param (int) ($mode) Play mode. Used when $r is not set. Optional.
	 * @param (int) ($r) Rank of that play. Not provided if printing player score, the function will calculate it. Optional.
	 */
	function printBeatmapScore($pid, $bmd5 = "", $mode = 0, $r = -1)
	{
		// Get score data
		$scoreData = $GLOBALS["db"]->fetch("SELECT * FROM scores WHERE id = ?", $pid);

		$replayID = $scoreData["id"];
		$playerName = $scoreData["username"];
		$score = $scoreData["score"];
		$maxCombo = $scoreData["max_combo"];
		$count50 = $scoreData["50_count"];
		$count100 = $scoreData["100_count"];
		$count300 = $scoreData["300_count"];
		$countMisses = $scoreData["misses_count"];
		$countKatu = $scoreData["katus_count"];
		$countGeki = $scoreData["gekis_count"];
		$fullCombo = $scoreData["full_combo"];
		$mods = $scoreData["mods"];
		$actualDate = osuDateToUNIXTimestamp($scoreData["time"]);

		// Check if this score has a replay
		if (file_exists("../web/replays/replay_".$replayID.".osr")) $hasReplay = 1; else $hasReplay = 0;

		// Get osu! user id for avatar
		$userID = getUserOsuID($playerName);

		// Get rank
		if ($r > -1)
		{
			// Top 50 score, rank is provided in arguments
			$rank = $r;
		}
		else
		{
			// User score, calculate rank manually
			//$rank = current($GLOBALS["db"]->fetch("SELECT COUNT(DISTINCT username) AS id FROM scores WHERE beatmap_md5 = ? AND username = ?", array($_GET["c"], $_GET["us"])));

			// Get all scores and loop trough all until user's one is found
			//$allScores = $GLOBALS["db"]->fetchAll("SELECT DISTINCT username FROM scores WHERE beatmap_md5 = ? AND completed = 2 ORDER BY score DESC", $bmd5);
			$allScores = $GLOBALS["db"]->fetchAll("SELECT DISTINCT username FROM scores WHERE beatmap_md5 = ? AND play_mode = ? AND completed = 3 ORDER BY score DESC", array($bmd5, $mode));
			$su = array();	// Users already in the leaderboard (we count only the best score per user)
			$r = 1;			// Last rank (we start from #1)

			for ($i=0; $i < count($allScores); $i++) {
				// Loop through all scores and get their rank

				// Check if current score is ours
				if ($allScores[$i]["username"] == $playerName)
				{
					// Score found! Save rank
					$rank = $r;
				}
				else
				{
					// Score is not ours
					// Check if we don't have another score by this user in the leaderboard
					if (!in_array($allScores[$i]["username"], $su))
					{
						// New user, check rank
						if (current($GLOBALS["db"]->fetch("SELECT allowed FROM users WHERE username = ?", $allScores[$i]["username"])) != 0)
						{
							// Not banned, increment rank
							$r++;
						}

						// Add in $su
						array_push($su, $allScores[$i]["username"]);
					}
				}

				// Add current user to array, so we don't add his lower scores
				array_push($su, $allScores[$i]["username"]);
			}
		}

		echo($replayID."|".$playerName."|".$score."|".$maxCombo."|".$count50."|".$count100."|".$count300."|".$countMisses."|".$countKatu."|".$countGeki."|".$fullCombo."|".$mods."|".$userID."|".$rank."|".$actualDate."|".$hasReplay."\r\n");
	}

	function printBeatmapMaintenance()
	{
		echo("0|ripple is in|8|0|0|0|0|0|0|0|0|0|0|0|0|0\r\n");
		echo("0|maintenance mode|7|0|0|0|0|0|0|0|0|0|0|0|0|0\r\n");
		echo("0|check|6|0|0|0|0|0|0|0|0|0|0|0|0|0\r\n");
		echo("0|your server's website|5|0|0|0|0|0|0|0|0|0|0|0|0|0\r\n");
		echo("0|for more info.|4|0|0|0|0|0|0|0|0|0|0|0|0|0\r\n");
		echo("0|SCORES WON'T BE SAVED!|3|0|0|0|0|0|0|0|0|0|0|0|0|0\r\n");
		echo("0|SCORES WON'T BE SAVED!!|2|0|0|0|0|0|0|0|0|0|0|0|0|0\r\n");
		echo("0|SCORES WON'T BE SAVED!!!|1|0|0|0|0|0|0|0|0|0|0|0|0|0\r\n");
	}

	function osuDateToUNIXTimestamp($date) {
		// phwr loves memes
		if ($date != 0) {
			$d = DateTime::createFromFormat("ymdHis", $date);
			$d->add(new DateInterval('PT1H'));
			return $d->getTimestamp();
		}
		else {
			return time() - 60 * 60 * 24; // Remove one day from the time because reasons
		}
	}

	/*
	 * sumScores
	 * Sum all the scores in $s array
	 * used in cron.php
	 *
	 * @param (array) ($s) score data array.
	 */
	function sumScores($s)
	{
		// Sum all scores provided in $s array
		$res = 0;
		if ($s)
		{
			for ($foo=0; $foo < count($s); $foo++) {
				$res += $s[$foo]["score"];
			}
		}
		return $res;
	}


	/*
	 * sumHits
	 * Sum all hits provided in $s scoredata array.
	 *
	 * @param (array) ($s) score data array.
	 */
	function sumHits($s)
	{
		//
		$res = 0;
		if ($s)
		{
			for ($bar=0; $bar < count($s); $bar++) {
				$res += $s[$bar]["300_count"];
				$res += $s[$bar]["100_count"];
				$res += $s[$bar]["50_count"];
			}
		}
		return $res;
	}


	/*
	 * getRequiredScoreForLevel
	 * Gets the required score for $l level
	 *
	 * @param (int) ($l) level
	 * @return (int) required score
	 */
	function getRequiredScoreForLevel($l)
	{
		// Calcolate required score
		if ($l <= 100)
		{
			if ($l >= 2)
				return 5000 / 3 * (4 * bcpow($l, 3, 0) - 3 * bcpow($l, 2, 0) - $l) + 1.25 * bcpow(1.8, $l - 60, 0);
			else if ($l <= 0 || $l = 1)
				return 1;	// Should be 0, but we get division by 0 below so set to 1
		}
		else if ($l >= 101)
		{
			return 26931190829 + 100000000000 * ($l - 100);
		}
	}


	/*
	 * getLevel
	 * Gets the level for $s score
	 *
	 * @param (int) ($s) ranked score number
	 */
	function getLevel($s)
	{
		$level = 1;
		while(true)
		{
			// if the level is > 8000, it's probably an endless loop. terminate it.
			if ($level > 8000) {
				return $level;
				break;
			}
			// Calculate required score
			$reqScore = getRequiredScoreForLevel($level);

			// Check if this is our level
			if ($s <= $reqScore)
			{
				// Our level, return it and break
				return $level;
				break;
			}
			else
			{
				// Not our level, calculate score for next level
				$level++;
			}
		}

	}


	/**************************
	 ** CHANGELOG FUNCTIONS  **
	 **************************/
	function getChangelog() {
		//global $GitLabConfig;
		sessionCheck();
		echo('<p align="center"><h1><i class="fa fa-bug"></i>	Changelog</h1>');
		echo('Welcome to the changelog page.<br>Here changes are posted real-time as they are published to the master branch.<br>Hover a change to know when it was done.<br><br>');
		if (!file_exists(dirname(__FILE__)."/../../ci-system/changelog.json")) {
			echo '<b>Unfortunately, the website owner did configure the changelog. Slap him off telling him to do it.</b>';
		}
		else {
			// We use only one page. For now. I'm tired. It's 23:46. Fuck everything.
			//$_GET["page"] = (isset($_GET["page"]) && $_GET["page"] > 0 ? intval($_GET["page"]) : 1);
			$data = getChangelogPage();
			foreach ($data as $commit) {
				echo sprintf("<div class='changelog-line' title='%s'>%s	<b>%s:</b> %s</div>", $commit["time"], $commit["labels"], $commit["username"], $commit["content"]);
			}
			echo "<br><br>";
			/*if ($_GET["page"] != 1) {
				echo "<a href='index.php?p=17&page=" . ($_GET["page"] - 1) . "'>&lt; Previous page</a>";
				echo " | ";
			}
			echo "<a href='index.php?p=17&page=" . ($_GET["page"] + 1) . "'>Next page &gt;</a>";*/
		}
	}

	/*
	 * getChangelogPage()
	 * Gets a page from the changelog.json with some commits.
	 *
	 * @param (int) ($p) Page. Optional. Default is 1.
	 */
	function getChangelogPage($p = 1) {
		global $ChangelogConfig;

		// Retrieve data from changelog.json
		$data = json_decode(file_get_contents(dirname(__FILE__)."/../../ci-system/changelog.json"), true);
		$ret = array();

		// Get each commit
		foreach ($data as $commit) {
			$a = getUserRank($_SESSION["username"]) >= 4 ? true : false;
			$b = false;
			$labels = "";

			// Check forbidden commits
			if (isset($ChangelogConfig["forbidden_commits"]))
			{
				foreach ($ChangelogConfig["forbidden_commits"] as $hash) {
					if (strpos($commit["commit"], strtolower($hash)) !== false) {
						$b = true;
						break;
					}
				}
			}

			// Only get first line of commit
			$commit["message"] = str_replace('-', ' ', explode("\n", $commit["message"])[0]);

			// Check forbidden words
			if (isset($ChangelogConfig["forbidden_keywords"]) && !empty($ChangelogConfig["forbidden_keywords"]))
			{
				foreach ($ChangelogConfig["forbidden_keywords"] as $word) {
					if (strpos(strtolower($commit["message"]), strtolower($word)) !== false) {
						$b = true;
						break;
					}
				}
			}

			// Add labels
			if (isset($ChangelogConfig["labels"]))
			{
				// Hidden label if user is an admin and commit is hidden
				if ($a && $b)
					$labels .= "<span class='label label-default'>Hidden</span>	";

				// Other labels
				foreach ($ChangelogConfig["labels"] as $label) {
					// Add label if needed
					$label = explode(",", $label);
					$keyword = $label[0];
					$text = $label[1];
					$color = $label[2];
					if (strpos(strtolower($commit["message"]), strtolower($keyword)) !== false) {
						$labels .= "<span class='label label-".$color."'>".$text."</span>	";
					}

					// Remove label keyword from commit
					$commit["message"] = str_ireplace($keyword, " ", $commit["message"]);
				}
			}

			// If we should not output this commit, let's skip it.
			if ($b && !$a)
				continue;

			// Change names if needed
			if (isset($ChangelogConfig["change_name"][$commit["author"]]))
				$commit["author"] = $ChangelogConfig["change_name"][$commit["author"]];

			// Build return array
			$ret[] = array(
				"username" => $commit["author"],
				"content" => htmlspecialchars($commit["message"]),
				"time" => $commit["date"],
				"labels" => $labels,
			);
		}
		return $ret;
	}


	/**************************
	 **   OTHER   FUNCTIONS  **
	 **************************/

	function get_contents_http($url){

		// If curl is not installed, attempt to use file_get_contents
		if (!function_exists('curl_init')){
			$w = stream_get_wrappers();
			if (in_array('http', $w))
				return file_get_contents($url);
			return;
		}

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);

		// Include header in result? (0 = yes, 1 = no)
		curl_setopt($ch, CURLOPT_HEADER, 0);

		// Should cURL return or print out the data? (true = return, false = print)
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// Timeout in seconds
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);

		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

		// Download the given URL, and return output
		$output = curl_exec($ch);

		/*
		if(curl_errno($ch))
		{
			echo 'error:' . curl_error($ch);
		}*/

		// Close the cURL resource, and free system resources
		curl_close($ch);

		return $output;
	}

	/*
	 * printBadgeSelect()
	 * Prints a select with every badge available as options
	 *
	 * @param (string) ($sn) Name of the select, for php form stuff
	 * @param (string) ($sid) Name of the selected item (badge ID)
	 * @param (array) ($bd) Badge data array (SELECT * FROM badges)
	 */
	function printBadgeSelect($sn, $sid, $bd)
	{
		echo('<select name="'.$sn.'" class="selectpicker" data-width="100%">');
		foreach ($bd as $b)	{
			if ($sid == $b["id"]) $sel = "selected"; else $sel = "";
			echo('<option value="'.$b["id"].'" '.$sel.'>'.$b["name"].'</option>');
		}
		echo('</select>');
	}

	/**
	* BwToString()
	* Bitwise enum number to string.
	*
	* @param (int) ($num) Number to convert to string
	* @param (array) ($bwenum) Bitwise enum in the form of array, $EnumName => $int
	* @param (string) ($sep) Separator
	*/
	function BwToString($num, $bwenum, $sep = "<br>") {
		$ret = array();
		foreach ($bwenum as $key => $value) {
			if ($num & $value) {
				$ret[] = $key;
			}
		}
		return implode($sep, $ret);
	}

?>
