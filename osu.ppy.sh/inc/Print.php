<?php

class P
{
    /*
    * AdminDashboard
    * Prints the admin panel dashborad page
    */
    public static function AdminDashboard()
    {
        // Get admin dashboard data
        $totalScores = current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM scores'));
        $betaKeysLeft = current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM beta_keys WHERE allowed = 1'));
        $rankedBeatmaps = current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM beatmaps WHERE ranked = 1'));
        //$suspiciousScores = current($GLOBALS["db"]->fetch("SELECT COUNT(*) FROM scores WHERE username = 'FokaWooooo'"));
        $reports = current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM reports WHERE status = 1'));
        $recentPlays = $GLOBALS['db']->fetchAll('SELECT * FROM scores ORDER BY id DESC LIMIT 10');
        $topPlays = $GLOBALS['db']->fetchAll('SELECT * FROM scores ORDER BY score DESC LIMIT 10');

        // Print admin dashboard
        echo '<div id="wrapper">';
        printAdminSidebar();
        echo '<div id="page-content-wrapper">';

        // Maintenance check
        self::MaintenanceStuff();

        // Global alert
        self::GlobalAlert();

        // Stats panels
        echo '<div class="row">';
        printAdminPanel('primary', 'fa fa-gamepad fa-5x', $totalScores, 'Total scores');
        printAdminPanel('red', 'fa fa-gift fa-5x', $betaKeysLeft, 'Beta keys left');
        printAdminPanel('green', 'fa fa-music fa-5x', $rankedBeatmaps, 'Ranked beatmaps');
        printAdminPanel('yellow', 'fa fa-paper-plane fa-5x', $reports, 'Opened reports');
        echo '</div>';

        // Recent plays table
        echo '<table class="table table-striped table-hover">
		<thead>
		<tr><th class="text-left"><i class="fa fa-clock-o"></i>	Recent plays</th><th>Beatmap</th></th><th>Mode</th><th>Sent</th><th class="text-right">Score</th></tr>
		</thead>
		<tbody>';

        foreach ($recentPlays as $play) {
            // Get beatmap name from md5 (beatmaps_names) for this play
            // TODO: fix calling database each fucking time.
            $bn = $GLOBALS['db']->fetch('SELECT beatmap_name FROM beatmaps_names WHERE beatmap_md5 = ?', $play['beatmap_md5']);

            // Check if this beatmap has a name cached, if yes show it, otherwise show its md5
            if ($bn) {
                $bn = current($bn);
            } else {
                $bn = current($GLOBALS['db']->fetch('SELECT beatmap_md5 FROM scores WHERE id = ?', $play['id']));
            }

            // Get readable play_mode
            $pm = getPlaymodeText($play['play_mode']);

            // Print row
            echo '<tr>';
            echo '<td class="success"><p class="text-left"><b>'.$play['username'].'</b></p></td>';
            echo '<td class="success"><p class="text-left">'.$bn.'</p></td>';
            echo '<td class="success"><p class="text-left">'.$pm.'</p></td>';
            echo '<td class="success"><p class="text-left">'.timeDifference(time(), osuDateToUNIXTimestamp($play['time'])).'</p></td>';
            echo '<td class="success"><p class="text-right"><b>'.number_format($play['score']).'</b></p></td>';
            echo '</tr>';
        }
        echo '</tbody>';

        // Top plays table
        echo '<table class="table table-striped table-hover">
		<thead>
		<tr><th class="text-left"><i class="fa fa-trophy"></i>	Top plays</th><th>Beatmap</th></th><th>Mode</th><th>Sent</th><th class="text-right">Score</th></tr>
		</thead>
		<tbody>';

        foreach ($topPlays as $play) {
            // Get beatmap name from md5 (beatmaps_names) for this play
            $bn = $GLOBALS['db']->fetch('SELECT beatmap_name FROM beatmaps_names WHERE beatmap_md5 = ?', $play['beatmap_md5']);

            // Check if this beatmap has a name cached, if yes show it, otherwise show its md5
            if ($bn) {
                $bn = current($bn);
            } else {
                $bn = current($GLOBALS['db']->fetch('SELECT beatmap_md5 FROM scores WHERE id = ?', $play['id']));
            }

            // Get readable play_mode
            $pm = getPlaymodeText($play['play_mode']);

            // Print row
            echo '<tr>';
            echo '<td class="warning"><p class="text-left"><b>'.$play['username'].'</b></p></td>';
            echo '<td class="warning"><p class="text-left">'.$bn.'</p></td>';
            echo '<td class="warning"><p class="text-left">'.$pm.'</p></td>';
            echo '<td class="warning"><p class="text-left">'.timeDifference(time(), osuDateToUNIXTimestamp($play['time'])).'</p></td>';
            echo '<td class="warning"><p class="text-right"><b>'.number_format($play['score']).'</b></p></td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</div>';
    }

    /*
    * AdminUsers
    * Prints the admin panel users page
    */
    public static function AdminUsers()
    {
        // Get admin dashboard data
        $totalUsers = current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM users'));
        $pendingUsers = current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM users WHERE allowed = 2'));
        $bannedUsers = current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM users WHERE allowed = 0'));
        $modUsers = current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM users WHERE rank >= 3'));

        // TODO: Multiple pages
        $users = $GLOBALS['db']->fetchAll('SELECT * FROM users');

        // Print admin dashboard
        echo '<div id="wrapper">';
        printAdminSidebar();
        echo '<div id="page-content-wrapper">';

        // Maintenance check
        self::MaintenanceStuff();

        // Print Success if set
        if (isset($_GET['s']) && !empty($_GET['s'])) {
            self::SuccessMessage($_GET['s']);
        }

        // Print Exception if set
        if (isset($_GET['e']) && !empty($_GET['e'])) {
            self::ExceptionMessage($_GET['e']);
        }

        // Stats panels
        echo '<div class="row">';
        printAdminPanel('primary', 'fa fa-user fa-5x', $totalUsers, 'Total users');
        printAdminPanel('red', 'fa fa-pause fa-5x', $pendingUsers, 'Pending users');
        printAdminPanel('yellow', 'fa fa-thumbs-down fa-5x', $bannedUsers, 'Banned users');
        printAdminPanel('green', 'fa fa-star fa-5x', $modUsers, 'Mod/Admins');
        echo '</div>';

        // Quick edit/silence/kick user button
        echo '<br><p align="center"><button type="button" class="btn btn-primary" data-toggle="modal" data-target="#quickEditUserModal">Quick edit user</button>';
        echo '&nbsp;&nbsp; <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#silenceUserModal">Silence user</button>';
        echo '&nbsp;&nbsp; <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#kickUserModal">Kick user from Bancho</button>';

        // Users plays table
        echo '<table class="table table-striped table-hover table-50-center">
		<thead>
		<tr><th class="text-center"><i class="fa fa-user"></i>	Users</th><th class="text-center">Username</th><th class="text-center">Rank</th><th class="text-center">Allowed</th><th class="text-center">Actions</th></tr>
		</thead>
		<tbody>';

        foreach ($users as $user) {
            // Set allowed label text/color
            switch ($user['allowed']) {
                case 0: $allowedColor = 'danger'; $allowedText = 'Banned'; break;
                case 1: $allowedColor = 'success'; $allowedText = 'Ok'; break;
                case 2: $allowedColor = 'warning'; $allowedText = 'Pending'; break;
            }

            // Set rank label text/color
            switch ($user['rank']) {
                case 1: $rankColor = 'success'; $rankText = 'User'; break;
                case 2: $rankColor = 'primary'; $rankText = 'Supporter'; break;
                case 3: $rankColor = 'info'; $rankText = 'Mod'; break;
                case 4: $rankColor = 'warning'; $rankText = 'Admin'; break;
            }

            // Print row
            echo '<tr>';
            echo '<td class="success"><p class="text-center">'.$user['id'].'</p></td>';
            echo '<td class="success"><p class="text-center"><b>'.$user['username'].'</b></p></td>';
            echo '<td class="success"><p class="text-center"><span class="label label-'.$rankColor.'">'.$rankText.'</span></p></td>';
            echo '<td class="success"><p class="text-center"><span class="label label-'.$allowedColor.'">'.$allowedText.'</span></p></td>';
            echo '<td class="success"><p class="text-center">
			<div class="btn-group">
			<a title="Edit user" class="btn btn-xs btn-primary" href="index.php?p=103&id='.$user['id'].'"><span class="glyphicon glyphicon-pencil"></span></a>';
            if ($user['allowed'] == 1) {
                echo '<a title="Ban user" class="btn btn-xs btn-warning" onclick="sure(\'submit.php?action=banUnbanUser&id='.$user['id'].'\')"><span class="glyphicon glyphicon-thumbs-down"></span></a>';
            } else {
                echo '<a title="Unban user" class="btn btn-xs btn-success" onclick="sure(\'submit.php?action=banUnbanUser&id='.$user['id'].'\')"><span class="glyphicon glyphicon-thumbs-up"></span></a>';
            }
            echo '	<a title="Change user identity" class="btn btn-xs btn-danger" href="index.php?p=104&id='.$user['id'].'"><span class="glyphicon glyphicon-refresh"></span></a>
			</div>
			</p></td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</div>';

        // Quick edit modal
        echo '<div class="modal fade" id="quickEditUserModal" tabindex="-1" role="dialog" aria-labelledby="quickEditUserModalLabel">
		<div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="quickEditUserModalLabel">Quick edit user</h4>
		</div>
		<div class="modal-body">
		<p>
		<form id="quick-edit-user-form" action="submit.php" method="POST">
		<input name="action" value="quickEditUser" hidden>
		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></span>
		<input type="text" name="u" class="form-control" placeholder="Username" aria-describedby="basic-addon1" required>
		</div>
		</form>
		</p>
		</div>
		<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		<button type="submit" form="quick-edit-user-form" class="btn btn-primary">Edit user</button>
		</div>
		</div>
		</div>
		</div>';

        // Silence user modal
        echo '<div class="modal fade" id="silenceUserModal" tabindex="-1" role="dialog" aria-labelledby="silenceUserModal">
		<div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="silenceUserModal">Silence user</h4>
		</div>
		<div class="modal-body">
		<p>
		<form id="silence-user-form" action="submit.php" method="POST">
		<input name="action" value="silenceUser" hidden>

		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></span>
		<input type="text" name="u" class="form-control" placeholder="Username" aria-describedby="basic-addon1" required>
		</div>

		<p style="line-height: 15px"></p>

		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-time" aria-hidden="true"></span></span>
		<input type="number" name="c" class="form-control" placeholder="How long" aria-describedby="basic-addon1" required>
		<select name="un" class="selectpicker" data-width="30%">
			<option value="1">Seconds</option>
			<option value="60">Minutes</option>
			<option value="3600">Hours</option>
			<option value="86400">Days</option>
		</select>
		</div>

		<p style="line-height: 15px"></p>

		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-comment" aria-hidden="true"></span></span>
		<input type="text" name="r" class="form-control" placeholder="Reason" aria-describedby="basic-addon1" required>
		</div>

		<p style="line-height: 15px"></p>

		That user will be silenced and kicked from the server. During the silence period, his client will be locked. <b>Max silence time is 7 days.</b>

		</form>
		</p>
		</div>
		<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		<button type="submit" form="silence-user-form" class="btn btn-primary">Silence user</button>
		</div>
		</div>
		</div>
		</div>';

        // Kick user modal
        echo '<div class="modal fade" id="kickUserModal" tabindex="-1" role="dialog" aria-labelledby="kickUserModalLabel">
		<div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="kickUserModalLabel">Kick user from Bancho</h4>
		</div>
		<div class="modal-body">
		<p>
		<form id="kick-user-form" action="submit.php" method="POST">
		<input name="action" value="kickUser" hidden>
		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></span>
		<input type="text" name="u" class="form-control" placeholder="Username" aria-describedby="basic-addon1" required>
		</div>
		</form>
		</p>
		</div>
		<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		<button type="submit" form="kick-user-form" class="btn btn-primary">Kick user</button>
		</div>
		</div>
		</div>
		</div>';
    }

    /*
    * AdminEditUser
    * Prints the admin panel edit user page
    */
    public static function AdminEditUser()
    {
        try {
            // Check if id is set
            if (!isset($_GET['id']) || empty($_GET['id'])) {
                throw new Exception('Invalid user ID!');
            }

            // Get user data
            $userData = $GLOBALS['db']->fetch('SELECT * FROM users WHERE id = ?', $_GET['id']);
            $userStatsData = $GLOBALS['db']->fetch('SELECT * FROM users_stats WHERE id = ?', $_GET['id']);

            // Check if this user exists
            if (!$userData || !$userStatsData) {
                throw new Exception("That user doesn't exist");
            }

            // Set readonly stuff
            $readonly[0] = '';        // User data stuff
            $readonly[1] = '';        // Username color/style stuff
            $selectDisabled = '';

            // Check if we are editing our account
            if ($userData['username'] == $_SESSION['username']) {
                // Allow to edit only user stats
                $readonly[0] = 'readonly';
                $selectDisabled = 'disabled';
            } elseif ($userData['rank'] >= getUserRank($_SESSION['username'])) {
                // We are trying to edit a user with same/higher rank than us :akerino:
                redirect("index.php?p=102&e=You dont't have enough permissions to edit this user");
                die();
            }

            // Print edit user stuff
            echo '<div id="wrapper">';
            printAdminSidebar();
            echo '<div id="page-content-wrapper">';

            // Maintenance check
            self::MaintenanceStuff();

            // Print Success if set
            if (isset($_GET['s']) && !empty($_GET['s'])) {
                self::SuccessMessage($_GET['s']);
            }

            // Print Exception if set
            if (isset($_GET['e']) && !empty($_GET['e'])) {
                self::ExceptionMessage($_GET['e']);
            }

            // Selected values stuff 1
            $selected[0] = [1 => '', 2 => '', 3 => '', 4 => ''];

            // Selected values stuff 2
            $selected[1] = [0 => '', 1 => '', 2 => ''];

            // Get selected stuff
            $selected[0][current($GLOBALS['db']->fetch('SELECT rank FROM users WHERE id = ?', $_GET['id']))] = 'selected';
            $selected[1][current($GLOBALS['db']->fetch('SELECT allowed FROM users WHERE id = ?', $_GET['id']))] = 'selected';

            echo '<p align="center"><font size=5><i class="fa fa-user"></i>	Edit user</font></p>';
            echo '<table class="table table-striped table-hover table-50-center">';
            echo '<tbody><form id="system-settings-form" action="submit.php" method="POST"><input name="action" value="saveEditUser" hidden>';
            echo '<tr>
			<td>ID</td>
			<td><p class="text-center"><input type="number" name="id" class="form-control" value="'.$userData['id'].'" readonly></td>
			</tr>';
            echo '<tr>
			<td>Username</td>
			<td><p class="text-center"><input type="text" name="u" class="form-control" value="'.$userData['username'].'" readonly></td>
			</tr>';
            echo '<tr>
			<td>Email</td>
			<td><p class="text-center"><input type="text" name="e" class="form-control" value="'.$userData['email'].'" '.$readonly[0].'></td>
			</tr>';
            echo '<tr>
			<td>Rank</td>
			<td>
			<select name="r" class="selectpicker" data-width="100%" '.$selectDisabled.'>
			<option value="1" '.$selected[0][1].'>User</option>
			<option value="2" '.$selected[0][2].'>Supporter</option>
			<option value="3" '.$selected[0][3].'>Mod (not working yet)</option>
			<option value="4" '.$selected[0][4].'>Admin</option>
			</select>
			</td>
			<!-- <td><p class="text-center"><input type="number" name="r" class="form-control" value="'.$userData['rank'].'" '.$readonly[0].'></td> -->
			</tr>';
            echo '<tr>
			<td>Allowed</td>
			<td>
			<select name="a" class="selectpicker" data-width="100%" '.$selectDisabled.'>
			<option value="0" '.$selected[1][0].'>Banned</option>
			<option value="1" '.$selected[1][1].'>Ok</option>
			<option value="2" '.$selected[1][2].'>Pending activation</option>
			</select>
			</td>
			<!-- <td><p class="text-center"><input type="number" name="a" class="form-control" value="'.$userData['allowed'].'" '.$readonly[0].'></td> -->
			</tr>';
            echo '<tr>
			<td>Username color<br>(HTML or HEX color)</td>
			<td><p class="text-center"><input type="text" name="c" class="form-control" value="'.$userStatsData['user_color'].'" '.$readonly[1].'></td>
			</tr>';
            echo '<tr>
			<td>Username style<br>(like fancy gifs as background)</td>
			<td><p class="text-center"><input type="text" name="bg" class="form-control" value="'.$userStatsData['user_style'].'" '.$readonly[1].'></td>
			</tr>';
            echo '<tr>
			<td>A.K.A</td>
			<td><p class="text-center"><input type="text" name="aka" class="form-control" value="'.htmlspecialchars($userStatsData['username_aka']).'"></td>
			</tr>';
            echo '<tr>
			<td>Userpage<br><a onclick="censorUserpage();">(reset userpage)</a></td>
			<td><p class="text-center"><textarea name="up" class="form-control" style="overflow:auto;resize:vertical;height:200px">'.$userStatsData['userpage_content'].'</textarea></td>
			</tr>';
            echo '<tr>
			<td>Silence end time<br><a onclick="removeSilence();">(remove silence)</a></td>
			<td><p class="text-center"><input type="text" name="se" class="form-control" value="'.$userData['silence_end'].'"></td>
			</tr>';
            echo '<tr>
			<td>Silence reason</td>
			<td><p class="text-center"><input type="text" name="sr" class="form-control" value="'.$userData['silence_reason'].'"></td>
			</tr>';
            echo '<tr>
			<td>Avatar</td>
			<td><img src="'.URL::Avatar().'/'.$_GET['id'].'" height="50" width="50"></img>	<a onclick="sure(\'submit.php?action=resetAvatar&id='.$_GET['id'].'\')">Reset avatar</a></td>
			</tr>';
            echo '</tbody></form>';
            echo '</table>';
            echo '<div class="text-center">
					<button type="submit" form="system-settings-form" class="btn btn-primary">Save changes</button><br><br>
					<a href="index.php?p=104&id='.$_GET['id'].'" class="btn btn-danger">Change identity</a>
					<a href="index.php?p=110&id='.$_GET['id'].'" class="btn btn-success">Edit badges</a>
					<a href="index.php?u='.$_GET['id'].'" class="btn btn-warning">View profile</a>
				</div>';
            echo '</div>';
        } catch (Exception $e) {
            // Redirect to exception page
            redirect('index.php?p=102&e='.$e->getMessage());
        }
    }

    /*
    * AdminChangeIdentity
    * Prints the admin panel change identity page
    */
    public static function AdminChangeIdentity()
    {
        try {
            // Get user data
            $userData = $GLOBALS['db']->fetch('SELECT * FROM users WHERE id = ?', $_GET['id']);
            $userStatsData = $GLOBALS['db']->fetch('SELECT * FROM users_stats WHERE id = ?', $_GET['id']);

            // Check if this user exists
            if (!$userData || !$userStatsData) {
                throw new Exception("That user doesn't exist");
            }

            // Check if we are trying to edit our account or a higher rank account
            if ($userData['username'] != $_SESSION['username'] && $userData['rank'] >= getUserRank($_SESSION['username'])) {
                throw new Exception("You dont't have enough permissions to edit this user.");
            }

            // Print edit user stuff
            echo '<div id="wrapper">';
            printAdminSidebar();
            echo '<div id="page-content-wrapper">';

            // Maintenance check
            self::MaintenanceStuff();

            // Print Success if set
            if (isset($_GET['s']) && !empty($_GET['s'])) {
                self::SuccessMessage($_GET['s']);
            }

            // Print Exception if set
            if (isset($_GET['e']) && !empty($_GET['e'])) {
                self::ExceptionMessage($_GET['e']);
            }

            echo '<p align="center"><font size=5><i class="fa fa-refresh"></i>	Change identity</font></p>';
            echo '<table class="table table-striped table-hover table-50-center">';
            echo '<tbody><form id="system-settings-form" action="submit.php" method="POST"><input name="action" value="changeIdentity" hidden>';
            echo '<tr>
			<td>ID</td>
			<td><p class="text-center"><input type="number" name="id" class="form-control" value="'.$userData['id'].'" readonly></td>
			</tr>';
            echo '<tr>
			<td>Old Username</td>
			<td><p class="text-center"><input type="text" name="oldu" class="form-control" value="'.$userData['username'].'" readonly></td>
			</tr>';
            echo '<tr class="success">
			<td>New Username</td>
			<td><p class="text-center"><input type="text" name="newu" class="form-control"></td>
			</tr>';
            echo '<tr>
			<td>Keep old scores<br>(with new username)</td>
			<td>
			<select name="ks" class="selectpicker" data-width="100%">
			<option value="1" selected>Yes</option>
			<option value="0">No</option>
			</select>
			</td>
			</tr>';
            echo '</tbody></form>';
            echo '</table>';
            echo '<div class="text-center"><button type="submit" form="system-settings-form" class="btn btn-primary">Change identity</button></div>';
            echo '</div>';
        } catch (Exception $e) {
            // Redirect to exception page
            redirect('index.php?p=102&e='.$e->getMessage());
        }
    }

    /*
    * AdminBetaKeys
    * Prints the admin panel beta keys page
    */
    public static function AdminBetaKeys()
    {
        // Get data
        $betaKeysLeft = current($GLOBALS['db']->fetch('SELECT COUNT(*) FROM beta_keys WHERE allowed = 1'));
        $betaKeys = $GLOBALS['db']->fetchAll('SELECT * FROM beta_keys ORDER BY allowed DESC');

        // Print beta keys stuff
        echo '<div id="wrapper">';
        printAdminSidebar();

        echo '<div id="page-content-wrapper">';

        // Maintenance check
        self::MaintenanceStuff();

        // Print Success if set
        if (isset($_GET['s']) && !empty($_GET['s'])) {
            self::SuccessMessage($_GET['s']);
        }

        // Print Exception if set
        if (isset($_GET['e']) && !empty($_GET['e'])) {
            self::ExceptionMessage($_GET['e']);
        }

        echo '<p align="center"><font size=5><i class="fa fa-gift"></i>	Beta keys</font></p>';
        echo '<p align="center">There are <b>'.$betaKeysLeft.'</b> Beta Keys left<br></p>';

        // Beta keys table
        echo '<table class="table table-striped table-hover table-75-center">
		<thead>
		<tr><th class="text-left"><i class="fa fa-gift"></i>	ID</th><th class="text-center">MD5</th><th class="text-center">Description</th><th class="text-center">Allowed</th><th class="text-center">Public</th><th class="text-center">Action</th></tr>
		</thead>
		<tbody>';

        for ($i = 0; $i < count($betaKeys); $i++) {
            // Set allowed label color and text
            if ($betaKeys[$i]['allowed'] == 0) {
                $allowedColor = 'danger';
                $allowedText = 'No';
            } else {
                $allowedColor = 'success';
                $allowedText = 'Yes';
            }

            // Set public label color and text
            if ($betaKeys[$i]['public'] == 0) {
                $publicColor = 'danger';
                $publicText = 'No';
            } else {
                $publicColor = 'success';
                $publicText = 'Yes';
            }

            // Print row
            echo '<tr>';
            echo '<td class="success"><p class="text-left"><b>'.$betaKeys[$i]['id'].'</b></p></td>';
            echo '<td class="success"><p class="text-center">'.$betaKeys[$i]['key_md5'].'</p></td>';
            echo '<td class="success"><p class="text-center">'.$betaKeys[$i]['description'].'</p></td>';
            echo '<td class="success"><p class="text-center"><span class="label label-'.$allowedColor.'">'.$allowedText.'</span></p></td>';
            echo '<td class="success"><p class="text-center"><span class="label label-'.$publicColor.'">'.$publicText.'</span></p></td>';

            // Delete button
            echo '<td class="success"><p class="text-center">
			<div class="btn-group"><a title="Delete beta key" class="btn btn-xs btn-danger" onclick="sure(\'submit.php?action=removeBetaKey&id='.$betaKeys[$i]['id'].'\')"><span class="glyphicon glyphicon-trash"></span></a>';

            // Allow/disallow button
            if ($betaKeys[$i]['allowed'] == 1) {
                echo '<a title="Disallow beta key (mark as already used)" class="btn btn-xs btn-warning" href="submit.php?action=allowDisallowBetaKey&id='.$betaKeys[$i]['id'].'"><span class="glyphicon glyphicon-thumbs-down"></span></a>';
            } else {
                echo '<a title="Allow beta key (mark as not used)" class="btn btn-xs btn-success" href="submit.php?action=allowDisallowBetaKey&id='.$betaKeys[$i]['id'].'"><span class="glyphicon glyphicon-thumbs-up"></span></a>';
            }

            // Public/private button
            if ($betaKeys[$i]['public'] == 1) {
                echo '<a title="Make private (hide on Beta keys page)" class="btn btn-xs btn-warning" href="submit.php?action=publicPrivateBetaKey&id='.$betaKeys[$i]['id'].'"><span class="glyphicon glyphicon-remove"></span></a>';
            } else {
                echo '<a title="Make public (show on Beta keys page)" class="btn btn-xs btn-success" href="submit.php?action=publicPrivateBetaKey&id='.$betaKeys[$i]['id'].'"><span class="glyphicon glyphicon-ok"></span></a>';
            }

            echo '</div></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';

        // Add beta key button
        echo '<p align="center"><button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addBetaKeyModal">Add beta keys</button></p>';

        echo '</div>';

        // Modal
        echo '<div class="modal fade" id="addBetaKeyModal" tabindex="-1" role="dialog" aria-labelledby="addBetaKeyModalLabel">
		<div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="addBetaKeyModalLabel">Keygen</h4>
		</div>
		<div class="modal-body">
		<p>
		<div class="wavetext"></div>
		<marquee loop="infinite">Bless me with your gift of lights. Righteous cause on judgment night: feel the sorrow the light has swallowed. Feel the freedom like no tomorrow...</marquee>
		<form id="beta-keys-form" action="submit.php" method="POST">
		<input name="action" value="generateBetaKeys" hidden>
		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-tag" aria-hidden="true"></span></span>
		<input type="number" name="n" class="form-control" placeholder="Number of Beta Keys to generate" aria-describedby="basic-addon1" required>
		</div><p style="line-height: 15px"></p>
		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-list-alt" aria-hidden="true"></span></span>
		<input type="text" name="d" maxlength="128" class="form-control" placeholder="Description (*key* will be replaced with the actual key)" aria-describedby="basic-addon1">
		</div>
		<p style="line-height: 15px"></p>
		<input type="checkbox" name="p">Public (show on Beta Keys page)<br>
		<b>If you add public keys, description will be ignored and replaced with *key*</b>
		</form>
		</p>
		</div>
		<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		<button type="submit" form="beta-keys-form" class="btn btn-primary">Add keys</button>
		</div>
		</div>
		</div>
		</div>';
    }

    /*
    * AdminReports
    * Prints the admin panel beta keys page
    */
    public static function AdminReports()
    {
        // Get data
        $reports = $GLOBALS['db']->fetchAll('SELECT * FROM reports ORDER BY id DESC');

        // Print beta keys stuff
        echo '<div id="wrapper">';
        printAdminSidebar();

        echo '<div id="page-content-wrapper">';

        // Maintenance check
        self::MaintenanceStuff();

        // Print Success if set
        if (isset($_GET['s']) && !empty($_GET['s'])) {
            self::SuccessMessage($_GET['s']);
        }

        // Print Exception if set
        if (isset($_GET['e']) && !empty($_GET['e'])) {
            self::ExceptionMessage($_GET['e']);
        }

        echo '<p align="center"><font size=5><i class="fa fa-paper-plane"></i>	Reports</font></p>';

        // Reports table
        echo '<table class="table table-striped table-hover table-75-center">
		<thead>
		<tr><th class="text-left"><i class"fa fa-gift"></i>	ID</th><th class="text-center">Type</th><th class="text-center">Name</th><th class="text-center">From</th><th class="text-center">Opened on</th><th class="text-center">Updated on</th><th class="text-center">Status</th><th class="text-center">Action</th></tr>
		</thead>
		<tbody>';

        for ($i = 0; $i < count($reports); $i++) {
            // Set status label color and text
            if ($reports[$i]['status'] == 1) {
                $statusColor = 'success';
                $statusText = 'Open';
            } else {
                $statusColor = 'danger';
                $statusText = 'Closed';
            }

            // Set type label color and text
            if ($reports[$i]['type'] == 1) {
                $typeColor = 'success';
                $typeText = 'Feature';
            } else {
                $typeColor = 'warning';
                $typeText = 'Bug';
            }

            // Print row
            echo '<tr>';
            echo '<td><p class="text-left">'.$reports[$i]['id'].'</p></td>';
            echo '<td><p class="text-center"><span class="label label-'.$typeColor.'">'.$typeText.'</span></p></td>';
            echo '<td><p class="text-center"><b>'.$reports[$i]['name'].'</b></p></td>';
            echo '<td><p class="text-center">'.$reports[$i]['from_username'].'</p></td>';
            echo '<td><p class="text-center">'.date('d/m/Y H:i:s', intval($reports[$i]['open_time'])).'</p></td>';
            echo '<td><p class="text-center">'.date('d/m/Y H:i:s', intval($reports[$i]['update_time'])).'</p></td>';
            echo '<td><p class="text-center"><span class="label label-'.$statusColor.'">'.$statusText.'</span></p></td>';

            // Edit button
            echo '
			<td><p class="text-center">
			<a title="View/Edit report" class="btn btn-xs btn-primary" href="index.php?p=114&id='.$reports[$i]['id'].'"><span class="glyphicon glyphicon-eye-open"></span></a>
			<a title="Open/Close report" class="btn btn-xs btn-success" href="submit.php?action=openCloseReport&id='.$reports[$i]['id'].'"><span class="glyphicon glyphicon-check"></span></a>
			</p></td>';

            // End row
            echo '</tr>';
        }
        echo '</tbody></table>';

        echo '</div>';
    }

    /*
    * AdminViewReport
    * Prints the admin panel view report page
    */
    public static function AdminViewReport()
    {
        try {
            // Check if id is set
            if (!isset($_GET['id'])) {
                throw new Exception('Invalid report id');
            }

            // Get report data
            $reportData = $GLOBALS['db']->fetch('SELECT * FROM reports WHERE id = ?', $_GET['id']);

            // Check if this report page exists
            if (!$reportData) {
                throw new Exception("That report doesn't exist");
            }

            // Set type label color and text
            if ($reportData['type'] == 1) {
                $typeColor = 'success';
                $typeText = 'Feature';
            } else {
                $typeColor = 'warning';
                $typeText = 'Bug';
            }

            // Selected thing
            $selected[0] = '';
            $selected[1] = '';
            $selected[$reportData['status']] = 'selected';

            // Print edit report stuff
            echo '<div id="wrapper">';
            printAdminSidebar();
            echo '<div id="page-content-wrapper">';

            // Maintenance check
            self::MaintenanceStuff();

            echo '<p align="center"><font size=5><i class="fa fa-pencil"></i>	Edit report</font></p>';
            echo '<table class="table table-striped table-hover table-50-center">';
            echo '<tbody>';
            echo '<form id="edit-report-form" action="submit.php" method="POST"><input name="action" value="saveEditReport" hidden>
			<input name="id" value="'.$reportData['id'].'" hidden>
			<tr>
			<td><b>ID</b></td>
			<td>'.$reportData['id'].'</td>
			</tr>';
            echo '<tr>
			<td><b>From</b></td>
			<td><a href="index.php?u='.getUserID($reportData['from_username']).'">'.$reportData['from_username'].'</a></td>
			</tr>';
            echo '<tr>
			<td><b>Type</b></td>
			<td><span class="label label-'.$typeColor.'">'.$typeText.'</span></td>
			</tr>';
            echo '<tr>
			<td><b>Status</b></td>
			<td>
			<select name="s" class="selectpicker" data-width="100%">
			<option value="1" '.$selected[1].'>Open</option>
			<option value="0" '.$selected[0].'>Close</option>
			</select>
			</td>
			</tr>';
            echo '<tr class="success">
			<td><b>Title</b></td>
			<td><b>'.htmlspecialchars($reportData['name']).'</b></td>
			</tr>';
            echo '<tr class="success">
			<td><b>Content</b></td>
			<td><i>'.htmlspecialchars($reportData['content']).'</i></td>
			</tr>';
            echo '<tr class="warning">
			<td><b>Response</b></td>
			<td><p class="text-center"><textarea name="r" class="form-control" style="overflow:auto;resize:vertical;height:100px">'.$reportData['response'].'</textarea></td>
			</tr>
			<tr class="warning">
			<td><b>Presets</b></td>
			<td>
			<a onclick="quickReportResponse(0);">Bug accepted</a> |
			<a onclick="quickReportResponse(1);">Bug already reported</a> |
			<a onclick="quickReportResponse(2);">Bug fixed</a><br>
			<a onclick="quickReportResponse(3);">Feature accepted</a> |
			<a onclick="quickReportResponse(4);">Feature already on tasklist</a> |
			<a onclick="quickReportResponse(5);">Feature added</a><br>
			<a onclick="quickReportResponse(6);">Abuse</a>
			</td>
			</tr>
			</form>';
            echo '</tbody>';
            echo '</table>';
            echo '<div class="text-center"><button type="submit" form="edit-report-form" class="btn btn-primary">Save changes</button></div>';
            echo '</div>';
        } catch (Exception $e) {
            // Redirect to exception page
            redirect('index.php?p=113&e='.$e->getMessage());
        }
    }

    /*
    * AdminSystemSettings
    * Prints the admin panel system settings page
    */
    public static function AdminSystemSettings()
    {
        // Print stuff
        echo '<div id="wrapper">';
        printAdminSidebar();

        echo '<div id="page-content-wrapper">';

        // Maintenance check
        self::MaintenanceStuff();

        // Print Success if set
        if (isset($_GET['s']) && !empty($_GET['s'])) {
            self::SuccessMessage($_GET['s']);
        }

        // Print Exception if set
        if (isset($_GET['e']) && !empty($_GET['e'])) {
            self::ExceptionMessage($_GET['e']);
        }

        // Get values
        $wm = current($GLOBALS['db']->fetch("SELECT value_int FROM system_settings WHERE name = 'website_maintenance'"));
        $gm = current($GLOBALS['db']->fetch("SELECT value_int FROM system_settings WHERE name = 'game_maintenance'"));
        $r = current($GLOBALS['db']->fetch("SELECT value_int FROM system_settings WHERE name = 'registrations_enabled'"));
        $ga = current($GLOBALS['db']->fetch("SELECT value_string FROM system_settings WHERE name = 'website_global_alert'"));
        $ha = current($GLOBALS['db']->fetch("SELECT value_string FROM system_settings WHERE name = 'website_home_alert'"));

        // Default select stuff
        $selected[0] = [1 => '', 2 => ''];
        $selected[1] = [1 => '', 2 => ''];
        $selected[2] = [1 => '', 2 => ''];

        // Checked stuff
        if ($wm == 1) {
            $selected[0][1] = 'selected';
        } else {
            $selected[0][2] = 'selected';
        }
        if ($gm == 1) {
            $selected[1][1] = 'selected';
        } else {
            $selected[1][2] = 'selected';
        }
        if ($r == 1) {
            $selected[2][1] = 'selected';
        } else {
            $selected[2][2] = 'selected';
        }

        echo '<p align="center"><font size=5><i class="fa fa-cog"></i>	System settings</font></p>';
        echo '<table class="table table-striped table-hover table-50-center">';
        echo '<tbody><form id="system-settings-form" action="submit.php" method="POST"><input name="action" value="saveSystemSettings" hidden>';
        echo '<tr>
		<td>Maintenance mode (website)</td>
		<td>
		<select name="wm" class="selectpicker" data-width="100%">
		<option value="1" '.$selected[0][1].'>On</option>
		<option value="0" '.$selected[0][2].'>Off</option>
		</select>
		</td>
		</tr>';
        echo '<tr>
		<td>Maintenance mode<br>(in-game)</td>
		<td>
		<select name="gm" class="selectpicker" data-width="100%">
		<option value="1" '.$selected[1][1].'>On</option>
		<option value="0" '.$selected[1][2].'>Off</option>
		</select>
		</td>
		</tr>';
        echo '<tr>
		<td>Registrations</td>
		<td>
		<select name="r" class="selectpicker" data-width="100%">
		<option value="1" '.$selected[2][1].'>On</option>
		<option value="0" '.$selected[2][2].'>Off</option>
		</select>
		</td>
		</tr>';
        echo '<tr>
		<td>Global alert<br>(visible on every page of the website)</td>
		<td><textarea type="text" name="ga" class="form-control" maxlength="512" style="overflow:auto;resize:vertical;height:100px">'.$ga.'</textarea></td>
		</tr>';
        echo '<tr>
		<td>Home alert<br>(visible only in homepage)</td>
		<td><textarea type="text" name="ha" class="form-control" maxlength="512" style="overflow:auto;resize:vertical;height:100px">'.$ha.'</textarea></td>
		</tr>';
        echo '<tr class="success"><td></td><td>For bancho settings, click <a href="index.php?p=111">here</a<</td></tr>';
        echo '</tbody></form>';
        echo '</table>';
        echo '<div class="text-center"><div class="btn-group" role="group">
		<button type="submit" form="system-settings-form" class="btn btn-primary">Save settings</button>
		<a title="Run cron.php script to refresh some stuff on the server" href="submit.php?action=runCron" type="button" class="btn btn-warning">Run cron.php</a>
		</div></div>';

        echo '</div>';
    }

    /*
    * AdminDocumentation
    * Prints the admin panel documentation files page
    */
    public static function AdminDocumentation()
    {
        // Get data
        $docsData = $GLOBALS['db']->fetchAll('SELECT * FROM docs');

        // Print docs stuff
        echo '<div id="wrapper">';
        printAdminSidebar();

        echo '<div id="page-content-wrapper">';

        // Maintenance check
        self::MaintenanceStuff();

        // Print Success if set
        if (isset($_GET['s']) && !empty($_GET['s'])) {
            self::SuccessMessage($_GET['s']);
        }

        // Print Exception if set
        if (isset($_GET['e']) && !empty($_GET['e'])) {
            self::ExceptionMessage($_GET['e']);
        }

        echo '<p align="center"><font size=5><i class="fa fa-book"></i>	Documentation</font></p>';
        echo '<table class="table table-striped table-hover table-50-center">';
        echo '<thead>
		<tr><th class="text-center"><i class="fa fa-book"></i>	ID</th><th class="text-center">Name</th><th class="text-center">Public</th><th class="text-center">Actions</th></tr>
		</thead>';
        echo '<tbody>';

        foreach ($docsData as $doc) {
            // Public label
            if ($doc['public'] == 1) {
                $publicColor = 'success';
                $publicText = 'Yes';
            } else {
                $publicColor = 'danger';
                $publicText = 'No';
            }

            // Print row for this doc page
            echo '<tr>
			<td><p class="text-center">'.$doc['id'].'</p></td>
			<td><p class="text-center">'.$doc['doc_name'].'</p></td>
			<td><p class="text-center"><span class="label label-'.$publicColor.'">'.$publicText.'</span></p></td>
			<td><p class="text-center">
			<a title="Edit page" class="btn btn-xs btn-primary" href="index.php?p=107&id='.$doc['id'].'"><span class="glyphicon glyphicon-pencil"></span></a>
			<a title="View page" class="btn btn-xs btn-success" href="index.php?p=16&id='.$doc['id'].'"><span class="glyphicon glyphicon-eye-open"></span></a>
			<a title="Delete page" class="btn btn-xs btn-danger" onclick="sure(\'submit.php?action=removeDoc&id='.$doc['id'].'\');"><span class="glyphicon glyphicon-trash"></span></a>
			</p></td>
			</tr>';
        }

        echo '</tbody>';
        echo '</table>';
        echo '<div class="text-center"><div class="btn-group" role="group">
		<a href="index.php?p=107&id=0" type="button" class="btn btn-primary">Add docs page</a>
		</div></div>';

        echo '</div>';
    }

    /*
    * AdminBadges
    * Prints the admin panel badges page
    */
    public static function AdminBadges()
    {
        // Get data
        $badgesData = $GLOBALS['db']->fetchAll('SELECT * FROM badges');

        // Print docs stuff
        echo '<div id="wrapper">';
        printAdminSidebar();

        echo '<div id="page-content-wrapper">';

        // Maintenance check
        self::MaintenanceStuff();

        // Print Success if set
        if (isset($_GET['s']) && !empty($_GET['s'])) {
            self::SuccessMessage($_GET['s']);
        }

        // Print Exception if set
        if (isset($_GET['e']) && !empty($_GET['e'])) {
            self::ExceptionMessage($_GET['e']);
        }

        echo '<p align="center"><font size=5><i class="fa fa-certificate"></i>	Badges</font></p>';
        echo '<table class="table table-striped table-hover table-50-center">';
        echo '<thead>
		<tr><th class="text-center"><i class="fa fa-certificate"></i>	ID</th><th class="text-center">Name</th><th class="text-center">Icon</th><th class="text-center">Actions</th></tr>
		</thead>';
        echo '<tbody>';

        foreach ($badgesData as $badge) {
            // Print row for this badge
            echo '<tr>
			<td><p class="text-center">'.$badge['id'].'</p></td>
			<td><p class="text-center">'.$badge['name'].'</p></td>
			<td><p class="text-center"><i class="fa '.$badge['icon'].' fa-2x"></i></p></td>
			<td><p class="text-center">
			<a title="Edit badge" class="btn btn-xs btn-primary" href="index.php?p=109&id='.$badge['id'].'"><span class="glyphicon glyphicon-pencil"></span></a>
			<a title="Delete badge" class="btn btn-xs btn-danger" onclick="sure(\'submit.php?action=removeBadge&id='.$badge['id'].'\');"><span class="glyphicon glyphicon-trash"></span></a>
			</p></td>
			</tr>';
        }

        echo '</tbody>';
        echo '</table>';
        echo '<div class="text-center">
			<a href="index.php?p=109&id=0" type="button" class="btn btn-primary">Add a new badge</a>
			<a type="button" class="btn btn-success" data-toggle="modal" data-target="#quickEditUserBadgesModal">Edit user badges</a>
		</div>';

        echo '</div>';

        // Quick edit modal
        echo '<div class="modal fade" id="quickEditUserBadgesModal" tabindex="-1" role="dialog" aria-labelledby="quickEditUserBadgesModalLabel">
		<div class="modal-dialog">
		<div class="modal-content">
		<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="quickEditUserBadgesModalLabel">Edit user badges</h4>
		</div>
		<div class="modal-body">
		<p>
		<form id="quick-edit-user-form" action="submit.php" method="POST">
		<input name="action" value="quickEditUserBadges" hidden>
		<div class="input-group">
		<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></span>
		<input type="text" name="u" class="form-control" placeholder="Username" aria-describedby="basic-addon1" required>
		</div>
		</form>
		</p>
		</div>
		<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		<button type="submit" form="quick-edit-user-form" class="btn btn-primary">Edit user badges</button>
		</div>
		</div>
		</div>
		</div>';
    }

    /*
    * AdminEditDocumentation
    * Prints the admin panel edit documentation file page
    */
    public static function AdminEditDocumentation()
    {
        try {
            // Check if id is set
            if (!isset($_GET['id'])) {
                throw new Exception('Invalid documentation page id');
            }

            // Check if we are editing or creating a new docs page
            if ($_GET['id'] > 0) {
                $docData = $GLOBALS['db']->fetch('SELECT * FROM docs WHERE id = ?', $_GET['id']);
            } else {
                $docData = ['id' => 0, 'doc_name' => 'New Documentation Page', 'doc_contents' => '', 'public' => 1];
            }

            // Check if this doc page exists
            if (!$docData) {
                throw new Exception("That documentation page doesn't exists");
            }

            // Print edit user stuff
            echo '<div id="wrapper">';
            printAdminSidebar();
            echo '<div id="page-content-wrapper">';

            // Maintenance check
            self::MaintenanceStuff();

            // Selected values stuff
            $selected[0] = [0 => '', 1 => ''];

            // Get selected stuff
            $selected[0][$docData['public']] = 'selected';

            echo '<p align="center"><font size=5><i class="fa fa-book"></i>	Edit documentation page</font></p>';
            echo '<table class="table table-striped table-hover table-75-center">';
            echo '<tbody><form id="edit-doc-form" action="submit.php" method="POST"><input name="action" value="saveDocFile" hidden>';
            echo '<tr>
			<td>ID</td>
			<td><p class="text-center"><input type="number" name="id" class="form-control" value="'.$docData['id'].'" readonly></td>
			</tr>';
            echo '<tr>
			<td>Page Name</td>
			<td><p class="text-center"><input type="text" name="t" class="form-control" value="'.$docData['doc_name'].'" ></td>
			</tr>';
            echo '<tr>
			<td>Page content</td>
			<td><textarea type="text" name="c" class="form-control" style="height: 200px;max-width:100%" spellcheck="false">'.$docData['doc_contents'].'</textarea></td>
			</tr>';
            echo '<tr class="success"><td></td><td>Tip: You can use markdown syntax instead of HTML syntax</td></tr>';
            echo '<tr>
			<td>Public</td>
			<td>
			<select name="p" class="selectpicker" data-width="100%">
			<option value="1" '.$selected[0][1].'>Yes</option>
			<option value="0" '.$selected[0][0].'>No</option>
			</select>
			</td>
			</tr>';
            echo '</tbody></form>';
            echo '</table>';
            echo '<div class="text-center"><button type="submit" form="edit-doc-form" class="btn btn-primary">Save changes</button></div>';
            echo '</div>';
        } catch (Exception $e) {
            // Redirect to exception page
            redirect('index.php?p=106&e='.$e->getMessage());
        }
    }

    /*
    * AdminEditBadge
    * Prints the admin panel edit badge page
    */
    public static function AdminEditBadge()
    {
        try {
            // Check if id is set
            if (!isset($_GET['id'])) {
                throw new Exception('Invalid badge id');
            }

            // Check if we are editing or creating a new badge
            if ($_GET['id'] > 0) {
                $badgeData = $GLOBALS['db']->fetch('SELECT * FROM badges WHERE id = ?', $_GET['id']);
            } else {
                $badgeData = ['id' => 0, 'name' => 'New Badge', 'icon' => ''];
            }

            // Check if this doc page exists
            if (!$badgeData) {
                throw new Exception("That badge doesn't exists");
            }

            // Print edit user stuff
            echo '<div id="wrapper">';
            printAdminSidebar();
            echo '<div id="page-content-wrapper">';

            // Maintenance check
            self::MaintenanceStuff();

            echo '<p align="center"><font size=5><i class="fa fa-certificate"></i>	Edit badge</font></p>';
            echo '<table class="table table-striped table-hover table-50-center">';
            echo '<tbody><form id="edit-badge-form" action="submit.php" method="POST"><input name="action" value="saveBadge" hidden>';
            echo '<tr>
			<td>ID</td>
			<td><p class="text-center"><input type="number" name="id" class="form-control" value="'.$badgeData['id'].'" readonly></td>
			</tr>';
            echo '<tr>
			<td>Name</td>
			<td><p class="text-center"><input type="text" name="n" class="form-control" value="'.$badgeData['name'].'" ></td>
			</tr>';
            echo '<tr>
			<td>Icon</td>
			<td><p class="text-center"><input type="text" name="i" class="form-control icp icp-auto" value="'.$badgeData['icon'].'" ></td>
			</tr>';
            echo '</tbody></form>';
            echo '</table>';
            echo '<div class="text-center"><button type="submit" form="edit-badge-form" class="btn btn-primary">Save changes</button></div>';
            echo '</div>';
        } catch (Exception $e) {
            // Redirect to exception page
            redirect('index.php?p=108&e='.$e->getMessage());
        }
    }

    /*
    * AdminEditUserBadges
    * Prints the admin panel edit user badges page
    */
    public static function AdminEditUserBadges()
    {
        try {
            // Check if id is set
            if (!isset($_GET['id'])) {
                throw new Exception('Invalid user id');
            }

            // Get user badges and explode
            $userBadges = explode(',', current($GLOBALS['db']->fetch('SELECT badges_shown FROM users_stats WHERE id = ?', $_GET['id'])));

            // Get username
            $username = current($GLOBALS['db']->fetch('SELECT username FROM users WHERE id = ?', $_GET['id']));

            // Get badges data
            $badgeData = $GLOBALS['db']->fetchAll('SELECT * FROM badges');

            // Print edit user badges stuff
            echo '<div id="wrapper">';
            printAdminSidebar();
            echo '<div id="page-content-wrapper">';

            // Maintenance check
            self::MaintenanceStuff();

            echo '<p align="center"><font size=5><i class="fa fa-certificate"></i>	Edit user badges</font></p>';
            echo '<table class="table table-striped table-hover table-50-center">';
            echo '<tbody><form id="edit-user-badges" action="submit.php" method="POST"><input name="action" value="saveUserBadges" hidden>';
            echo '<tr>
			<td>Username</td>
			<td><p class="text-center"><input type="text" name="u" class="form-control" value="'.$username.'" readonly></td>
			</tr>';
            echo '<tr>
			<td>Badge 1</td>
			<td>';
            printBadgeSelect('b01', $userBadges[0], $badgeData);
            echo '</td>
			</tr>';
            echo '<tr>
			<td>Badge 2</td>
			<td>';
            printBadgeSelect('b02', $userBadges[1], $badgeData);
            echo '</td>
			</tr>';
            echo '<tr>
			<td>Badge 3</td>
			<td>';
            printBadgeSelect('b03', $userBadges[2], $badgeData);
            echo '</td>
			</tr>';
            echo '<tr>
			<td>Badge 4</td>
			<td>';
            printBadgeSelect('b04', $userBadges[3], $badgeData);
            echo '</td>
			</tr>';
            echo '<tr>
			<td>Badge 5</td>
			<td>';
            printBadgeSelect('b05', $userBadges[4], $badgeData);
            echo '</td>
			</tr>';
            echo '<tr>
			<td>Badge 6</td>
			<td>';
            printBadgeSelect('b06', $userBadges[5], $badgeData);
            echo '</td>
			</tr>';
            echo '</tbody></form>';
            echo '</table>';
            echo '<div class="text-center"><button type="submit" form="edit-user-badges" class="btn btn-primary">Save changes</button></div>';
            echo '</div>';
        } catch (Exception $e) {
            // Redirect to exception page
            redirect('index.php?p=108&e='.$e->getMessage());
        }
    }

    /*
    * AdminBanchoSettings
    * Prints the admin panel bancho settings page
    */
    public static function AdminBanchoSettings()
    {
        // Print stuff
        echo '<div id="wrapper">';
        printAdminSidebar();

        echo '<div id="page-content-wrapper">';

        // Maintenance check
        self::MaintenanceStuff();

        // Print Success if set
        if (isset($_GET['s']) && !empty($_GET['s'])) {
            self::SuccessMessage($_GET['s']);
        }

        // Print Exception if set
        if (isset($_GET['e']) && !empty($_GET['e'])) {
            self::ExceptionMessage($_GET['e']);
        }

        // Get values
        $bm = current($GLOBALS['db']->fetch("SELECT value_int FROM bancho_settings WHERE name = 'bancho_maintenance'"));
        $od = current($GLOBALS['db']->fetch("SELECT value_int FROM bancho_settings WHERE name = 'free_direct'"));
        $rm = current($GLOBALS['db']->fetch("SELECT value_int FROM bancho_settings WHERE name = 'restricted_joke'"));
        $mi = current($GLOBALS['db']->fetch("SELECT value_string FROM bancho_settings WHERE name = 'menu_icon'"));
        $lm = current($GLOBALS['db']->fetch("SELECT value_string FROM bancho_settings WHERE name = 'login_messages'"));
        $ln = current($GLOBALS['db']->fetch("SELECT value_string FROM bancho_settings WHERE name = 'login_notification'"));
        $cv = current($GLOBALS['db']->fetch("SELECT value_string FROM bancho_settings WHERE name = 'osu_versions'"));
        $cmd5 = current($GLOBALS['db']->fetch("SELECT value_string FROM bancho_settings WHERE name = 'osu_md5s'"));

        // Default select stuff
        $selected[0] = [1 => '', 2 => ''];
        $selected[1] = [1 => '', 2 => ''];
        $selected[2] = [1 => '', 2 => ''];

        // Checked stuff
        if ($bm == 1) {
            $selected[0][1] = 'selected';
        } else {
            $selected[0][2] = 'selected';
        }
        if ($rm == 1) {
            $selected[1][1] = 'selected';
        } else {
            $selected[1][2] = 'selected';
        }
        if ($od == 1) {
            $selected[2][1] = 'selected';
        } else {
            $selected[2][2] = 'selected';
        }

        echo '<p align="center"><font size=5><i class="fa fa-server"></i>	Bancho settings</font></p>';
        echo '<table class="table table-striped table-hover table-50-center">';
        echo '<tbody><form id="system-settings-form" action="submit.php" method="POST"><input name="action" value="saveBanchoSettings" hidden>';
        echo '<tr>
		<td>Maintenance mode<br>(bancho)</td>
		<td>
		<select name="bm" class="selectpicker" data-width="100%">
		<option value="1" '.$selected[0][1].'>On</option>
		<option value="0" '.$selected[0][2].'>Off</option>
		</select>
		</td>
		</tr>';
        echo '<tr>
		<td>Restricted mode joke</td>
		<td>
		<select name="rm" class="selectpicker" data-width="100%">
		<option value="1" '.$selected[1][1].'>On</option>
		<option value="0" '.$selected[1][2].'>Off</option>
		</select>
		</td>
		</tr>';
        echo '<tr>
		<td>Free osu!direct</td>
		<td>
		<select name="od" class="selectpicker" data-width="100%">
		<option value="1" '.$selected[2][1].'>On</option>
		<option value="0" '.$selected[2][2].'>Off</option>
		</select>
		</td>
		</tr>';
        echo '<tr>
		<td>Menu bottom icon<br>(imageurl|clickurl)</td>
		<td><p class="text-center"><input type="text" value="'.$mi.'" name="mi" class="form-control"></td>
		</tr>';
        echo '<tr>
		<td>Login #osu messages<br>One per line<br>(user|message)</td>
		<td><textarea type="text" name="lm" class="form-control" maxlength="512" style="overflow:auto;resize:vertical;height:100px">'.$lm.'</textarea></td>
		</tr>';
        echo '<tr>
		<td>Login notification</td>
		<td><textarea type="text" name="ln" class="form-control" maxlength="512" style="overflow:auto;resize:vertical;height:100px">'.$ln.'</textarea></td>
		</tr>';
        echo '<tr>
		<td>Supported osu! versions<br>(separated by |)</td>
		<td><p class="text-center"><input type="text" value="'.$cv.'" name="cv" class="form-control"></td>
		</tr>';
        echo '<tr>
		<td>Supported osu!.exe md5s<br>(separated by |)</td>
		<td><p class="text-center"><input type="text" value="'.$cmd5.'" name="cmd5" class="form-control"></td>
		</tr>';
        echo '</tbody><table>
		<div class="text-center"><button type="submit" class="btn btn-primary">Save settings</button></div></form>';

        echo '</div>';
    }

    /*
    * AdminChatlog
    * Prints the admin chatlog page
    */
    public static function AdminChatlog()
    {
        // Get page
        $page = 0;
        if (isset($_GET['pg'])) {
            $page = $_GET['pg'];
        }

        // Get start and end
        $start = 50 * $page;
        $end = 50 * ($page + 1);

        // Get data
        $chatData = $GLOBALS['db']->fetchAll('SELECT * FROM bancho_messages ORDER BY id DESC LIMIT '.$start.','.$end);

        echo '<div id="wrapper">';
        printAdminSidebar();

        echo '<div id="page-content-wrapper">';

        // Maintenance check
        self::MaintenanceStuff();

        // Print Success if set
        if (isset($_GET['s']) && !empty($_GET['s'])) {
            self::SuccessMessage($_GET['s']);
        }

        // Print Exception if set
        if (isset($_GET['e']) && !empty($_GET['e'])) {
            self::ExceptionMessage($_GET['e']);
        }

        echo '<p align="center"><font size=5><i class="fa fa-comment"></i>	Chatlog</font></p>';
        echo '<table class="table table-striped table-hover">';
        echo '<thead>
		<tr><th class="text-center"><i class="fa fa-comment"></i>	ID</th><th class="text-center">From</th><th class="text-center">To</th><th class="text-center">Message</th><th class="text-center">Time</th></tr>
		</thead>';
        echo '<tbody>';

        foreach ($chatData as $message) {
            // Print row for this badge
            echo '<tr>
			<td><p class="text-center">'.$message['id'].'</p></td>
			<td><p class="text-center"><b>'.$message['msg_from_username'].'</b></p></td>
			<td><p class="text-center">'.$message['msg_to'].'</p></td>
			<td><p class="text-center"><b>'.$message['msg'].'</b></p></td>
			<td><p class="text-center">'.timeDifference(time(), $message['time']).'</p></td>
			</tr>';
        }

        echo '</tbody>';
        echo '</table>';

        echo '<p align="center">';

        if ($page > 0) {
            echo '<a href="index.php?p=112&pg='.($page - 1).'">< Previous Page</a>';
        }

        echo '&nbsp;&nbsp;&nbsp;&nbsp;';

        if ($chatData) {
            echo '<a href="index.php?p=112&pg='.($page + 1).'">Next Page ></a>';
        }

        echo '</p>';

        echo '</div>';
    }

    /*
    * HomePage
    * Prints the homepage
    */
    public static function HomePage()
    {
        // Home success message
        $success = [
            'forgetDone' => 'Done! Your "Stay logged in" tokens have been deleted from the database.',
        ];
        $error = [
             1 => 'You are already logged in.',
        ];
        if (!empty($_GET['s']) && isset($success[$_GET['s']])) {
            self::SuccessMessage($success[$_GET['s']]);
        }
        if (!empty($_GET['e']) && isset($error[$_GET['e']])) {
            self::ExceptionMessage($error[$_GET['e']]);
        }

        echo '<p align="center"><br><image class="animated bounce" src="'.(mt_rand(0, 100) == 1 ? '//i.imgur.com/CBKmTji.jpg' : './images/logo-256.png').'"></image><br></p><h1 class="animated bounceIn">Welcome to Ripple 1.5</h1>';

        // Home alert
        self::HomeAlert();
    }

    /*
    * UserPage
    * Print user page for $u user
    *
    * @param (int) ($u) ID of user.
    * @param (int) ($m) Playmode.
    */
    public static function UserPage($u, $m = -1)
    {
        // Maintenance check
        self::MaintenanceStuff();

        // Global alert
        self::GlobalAlert();

        try {
            // Check if the user is in db
            if (!$GLOBALS['db']->fetch('SELECT id FROM users WHERE id = ?', $u)) {
                throw new Exception('User not found');
            }

            // globals
            global $PlayStyleEnum;

            // Check banned status
            $allowed = current($GLOBALS['db']->fetch('SELECT allowed FROM users WHERE id = ?', [$u]));

            // Throw exception if user is banned/not activated
            // print message if we are admin
            if ($allowed == 0) {
                if (getUserRank($_SESSION['username']) <= 2) {
                    throw new Exception('User banned');
                } else {
                    echo '<div class="alert alert-danger" role="alert"><i class="fa fa-exclamation-triangle"></i>	<b>User banned.</b></div>';
                }
            }

            // Get all user stats for all modes and username
            $userData = $GLOBALS['db']->fetch('SELECT * FROM users_stats WHERE id = ?', $u);
            $username = current($GLOBALS['db']->fetch('SELECT username FROM users WHERE id = ?', $u));

            // Set default modes texts, selected is bolded below
            $modesText = [
                0 => 'osu!standard',
                1 => 'Taiko',
                2 => 'Catch the Beat',
                3 => 'osu!mania',
            ];

            // Get stats for selected mode
            $m = ($m < 0 || $m > 3 ? $userData['favourite_mode'] : $m);
            $modeForDB = getPlaymodeText($m);
            $modeReadable = getPlaymodeText($m, true);

            // Standard stats
            $rankedScore = $userData['ranked_score_'.$modeForDB];
            $totalScore = $userData['total_score_'.$modeForDB];
            $playCount = $userData['playcount_'.$modeForDB];
            $totalHits = $userData['total_hits_'.$modeForDB];
            $accuracy = $userData['avg_accuracy_'.$modeForDB];
            $replaysWatchedByOthers = $userData['replays_watched_'.$modeForDB];
            $country = $userData['country'];
            $showCountry = $userData['show_country'];
            $usernameAka = $userData['username_aka'];
            $level = $userData['level_'.$modeForDB] - 1;
            $latestActivity = current($GLOBALS['db']->fetch('SELECT latest_activity FROM users WHERE username = ?', $username));
            $silenceEndTime = current($GLOBALS['db']->fetch('SELECT silence_end FROM users WHERE username = ?', $username));
            $silenceReason = current($GLOBALS['db']->fetch('SELECT silence_reason FROM users WHERE username = ?', $username));
            $maximumCombo = $GLOBALS['db']->fetch('SELECT max_combo FROM scores WHERE username = ? AND play_mode = ? ORDER BY max_combo DESC LIMIT 1', [$username, $m]);
            if ($maximumCombo) {
                $maximumCombo = current($maximumCombo);
            } else {
                $maximumCombo = 0;
            }    // Make sure that we have at least one score to calculate maximum combo, otherwise maximum combo is 0

            // Get username style (for random funny stuff lmao)
            if ($silenceEndTime - time() > 0) {
                $userStyle = 'text-decoration: line-through;';
            } else {
                $userStyle = current($GLOBALS['db']->fetch('SELECT user_style FROM users_stats WHERE id = ?', $u));
            }

            // Get top/recent plays for this mode
            $topPlays = $GLOBALS['db']->fetchAll('SELECT * FROM scores WHERE username = ? AND completed = 3 AND play_mode = ? ORDER BY score DESC LIMIT 10', [$username, $m]);
            $recentPlays = $GLOBALS['db']->fetchAll('SELECT * FROM scores WHERE username = ? AND completed = 3 AND play_mode = ? ORDER BY time DESC LIMIT 10', [$username, $m]);

            // Get all allowed users on Ripple
            $allowedUsers = getAllowedUsers('id');

            // Bold selected mode text.
            $modesText[$m] = '<b>'.$modesText[$m].'</b>';

            // Get userpage
            $userpageContent = $userData['userpage_content'];

            // Friend button
            if (!checkLoggedIn() || $u == getUserID($_SESSION['username'])) {
                $friendButton = '';
            } else {
                $friendship = getFriendship($_SESSION['username'], $username);
                switch ($friendship) {
                    case 1: $friendButton = '<div id="friend"><a href="submit.php?action=addRemoveFriend&u='.$u.'" type="button" class="btn btn-success"><span class="glyphicon glyphicon-star"></span>	Friend</a></div>'; break;
                    case 2: $friendButton = '<div id="friend-mutual"><a href="submit.php?action=addRemoveFriend&u='.$u.'" type="button" class="btn btn-danger"><span class="glyphicon glyphicon-heart"></span>	Mutual Friend</a></div>'; break;
                    default: $friendButton = '<div id="friend-add"><a href="submit.php?action=addRemoveFriend&u='.$u.'" type="button" class="btn btn-primary"><span class="glyphicon glyphicon-plus"></span>	Add as Friend</a></div>'; break;
                }
            }

            // Calculate rank
            $rank = intval(Leaderboard::GetUserRank($u, $modeForDB));
            // Set rank char (trophy for top 3, # for everyone else)
            if ($rank <= 3) {
                $rankSymbol = '<i class="fa fa-trophy"></i> ';
            } else {
                $rank = sprintf('%02d', $rank);
                $rankSymbol = '#';
            }

            // Get badges id and icon (max 6 badges)
            $badgeID = explode(',', $userData['badges_shown']);
            for ($i = 0; $i < count($badgeID); $i++) {
                $badgeIcon[$i] = $GLOBALS['db']->fetch('SELECT icon FROM badges WHERE id = ?', $badgeID[$i]);
                $badgeName[$i] = $GLOBALS['db']->fetch('SELECT name FROM badges WHERE id = ?', $badgeID[$i]);
                if ($badgeIcon[$i]) {
                    $badgeIcon[$i] = current($badgeIcon[$i]);
                } else {
                    $badgeIcon[$i] = 0;
                }
                if ($badgeName[$i]) {
                    $badgeName[$i] = current($badgeName[$i]);
                } else {
                    $badgeName[$i] = '';
                }
            }

            // Userpage custom stuff
            if (strlen($userpageContent) > 0) {
                // BB Code parser
                require_once 'bbcode.php';

                // Collapse type (if < 350 chars, userpage will be shown)
                if (strlen($userpageContent) <= 350) {
                    $ct = 'in';
                } else {
                    $ct = 'out';
                }

                // Print userpage content
                //echo('<div class="panel panel-default"><div class="panel-body">'.$bbcode->toHTML($userpageContent, true).'</div></div>');
                echo '<div class="spoiler">
						<div class="panel panel-default">
							<div class="panel-heading">
								<button type="button" class="btn btn-default btn-xs spoiler-trigger" data-toggle="collapse">Expand userpage</button>';
                if ($username == $_SESSION['username']) {
                    echo '	<a href="index.php?p=8" type="button" class="btn btn-default btn-xs"><i>Edit</i></a>';
                }
                echo '</div>
							<div class="panel-collapse collapse '.$ct.'">
								<div class="panel-body">'.bbcode::toHtml($userpageContent, true).'</div>
							</div>
						</div>
					</div>';
            }

            // Userpage header
            // 1.5 -- Add quick admin commands
            echo '<div id="userpage-header">
			<!-- Avatar, username and rank -->
			<p><img id="user-avatar" src="'.URL::Avatar().'/'.$u.'" height="100" width="100" /></p>
			<p id="username"><font size=5><b>';
            if ($country != 'XX' && $showCountry == 1) {
                echo '<img src="./images/flags/'.strtolower($country).'.png">	';
            }
            echo '<font color="'.$userData['user_color'].'" style="'.$userStyle.'">'.$username.'</font></b></font>	';
            if ($usernameAka != '') {
                echo '<small><i>A.K.A '.htmlspecialchars($usernameAka).'</i></small>';
            }
            echo '<br><a href="index.php?u='.$u.'&m=0">'.$modesText[0].'</a> | <a href="index.php?u='.$u.'&m=1">'.$modesText[1].'</a> | <a href="index.php?u='.$u.'&m=2">'.$modesText[2].'</a> | <a href="index.php?u='.$u.'&m=3">'.$modesText[3].'</a>';
            if (getUserRank($_SESSION['username']) >= 4) {
                echo '<br><a href="index.php?p=103&id='.$u.'">Edit user</a> | <a onclick="sure(\'submit.php?action=banUnbanUser&id='.$u.'\')";>Ban user</a> | <a href="index.php?p=110&id='.$u.'">Edit badges</a></p>';
            }
            echo '<div id="rank"><font size=5><b> '.$rankSymbol.$rank.'</b></font></div>';
            echo $friendButton;
            echo '</div>';
            echo '<div id="userpage-content">
			<div class="col-md-3">';

            // Badges Left colum
            if ($badgeID[0] > 0) {
                echo '<i class="fa '.$badgeIcon[0].' fa-2x"></i><br><b>'.$badgeName[0].'</b><br><br>';
            }
            if ($badgeID[2] > 0) {
                echo '<i class="fa '.$badgeIcon[2].' fa-2x"></i><br><b>'.$badgeName[2].'</b><br><br>';
            }
            if ($badgeID[4] > 0) {
                echo '<i class="fa '.$badgeIcon[4].' fa-2x"></i><br><b>'.$badgeName[4].'</b><br><br>';
            }

            echo '</div>
			<div class="col-md-3">';

            // Badges Right column
            if ($badgeID[1] > 0) {
                echo '<i class="fa '.$badgeIcon[1].' fa-2x"></i><br><b>'.$badgeName[1].'</b><br><br>';
            }
            if ($badgeID[3] > 0) {
                echo '<i class="fa '.$badgeIcon[3].' fa-2x"></i><br><b>'.$badgeName[3].'</b><br><br>';
            }
            if ($badgeID[5] > 0) {
                echo '<i class="fa '.$badgeIcon[5].' fa-2x"></i><br><b>'.$badgeName[5].'</b><br><br>';
            }

            // Calculate required score for our level
            $reqScore = getRequiredScoreForLevel($level);
            $reqScoreNext = getRequiredScoreForLevel($level + 1);
            $scoreDiff = $reqScoreNext - $reqScore;
            $ourScore = $reqScoreNext - $totalScore;

            $percText = 100 - floor((100 * $ourScore) / ($scoreDiff + 1));                    // Text percentage, real one
            if ($percText < 10) {
                $percBar = 10;
            } else {
                $percBar = $percText;
            }    // Progressbar percentage, minimum 10 or it's glitched

            echo '</div><div class="col-md-6">
			<!-- Stats -->
			<b>Level '.$level.'</b>
			<div class="progress">
			<div class="progress-bar" role="progressbar" aria-valuenow="'.$percBar.'" aria-valuemin="10" aria-valuemax="100" style="width:'.$percBar.'%">'.$percText.'%</div>
			</div>
			<table>
			<tr>
			<td id="stats-name">Ranked Score</td>
			<td id="stats-value"><b>'.number_format($rankedScore).'</b></td>
			</tr>
			<tr>
			<td id="stats-name">Total score</td>
			<td id="stats-value">'.number_format($totalScore).'</td>
			<tr>
			<td id="stats-name">Play Count</td>
			<td id="stats-value"><b>'.number_format($playCount).'</b></td>
			</tr>
			<tr>
			<td id="stats-name">Hit Accuracy</td>
			<td id="stats-value"><b>'.(is_numeric($accuracy) ? accuracy($accuracy) : '0.00').'%</b></td>
			</tr>
			<tr>
			<td id="stats-name">Total Hits</td>
			<td id="stats-value"><b>'.number_format($totalHits).'</b></td>
			</tr>
			<tr>
			<td id="stats-name">Maximum Combo</td>
			<td id="stats-value"><b>'.number_format($maximumCombo).'</b></td>
			</tr>
			<tr>
				<td id="stats-name">Replays watched by others</td>
				<td id="stats-value"><b>'.number_format($replaysWatchedByOthers).'</b></td>
			</tr>';

            // Country
            if ($showCountry) {
                echo '<tr><td id="stats-name">From</td><td id="stats-value"><b>'.countryCodeToReadable($country).'</b></td></tr>';
            }

            // Show latest activity only if it's valid
            if ($latestActivity != 0) {
                echo '<tr>
				<td id="stats-name">Latest activity</td>
				<td id="stats-value"><b>'.timeDifference(time(), $latestActivity).'</b></td>
			</tr>';
            }

            // Playstyle
            if ($userData['play_style'] > 0) {
                echo '<tr><td id="stats-name">Play style</td><td id="stats-value"><b>'.BwToString($userData['play_style'], $PlayStyleEnum).'</b></td></tr>';
            }

            echo '</table>
			</div>
			</div>
			<div id ="userpage-plays">';

            // Print top plays table (only if we have them)
            if ($topPlays) {
                echo '<table class="table">
				<tr><th class="text-left"><i class="fa fa-trophy"></i>	Top plays</th><th class="text-right">Accuracy</th><th class="text-right">Score</th></tr>';
                for ($i = 0; $i < count($topPlays); $i++) {
                    // Get beatmap name from md5 (beatmaps_names) for this play
                    $bn = $GLOBALS['db']->fetch('SELECT beatmap_name FROM beatmaps_names WHERE beatmap_md5 = ?', $topPlays[$i]['beatmap_md5']);

                    if ($bn) {
                        // Beatmap name found, print beatmap name and score
                        echo '<tr>';
                        echo '<td class="warning"><p class="text-left">'.current($bn).' <b>'.getScoreMods($topPlays[$i]['mods']).'</b><br><small>'.timeDifference(time(), osuDateToUNIXTimestamp($topPlays[$i]['time'])).'</small>'.'</b></p></td>';
                        echo '<td class="warning"><p class="text-right">'.accuracy($topPlays[$i]['accuracy']).'%</p></td>';
                        echo '<td class="warning"><p class="text-right"><b>'.number_format($topPlays[$i]['score']).'</b>	<a href="/web/osu-getreplay-full.php?c='.$topPlays[$i]['id'].'"><i class="fa fa-star"></i></a></p></td>';
                        echo '</tr>';
                    }
                }
                echo '</table>';
            }

            // brbr it's so cold
            echo '<br><br>';

            // Print recent plays table (only if we have them)
            if ($recentPlays) {
                echo '<table class="table">
				<tr><th class="text-left"><i class="fa fa-clock-o"></i>	Recent plays</th><th class="text-right">Accuracy</th><th class="text-right">Score</th></tr>';
                for ($i = 0; $i < count($recentPlays); $i++) {
                    // Get beatmap name from md5 (beatmaps_names) for this play
                    $bn = $GLOBALS['db']->fetch('SELECT beatmap_name FROM beatmaps_names WHERE beatmap_md5 = ?', $recentPlays[$i]['beatmap_md5']);

                    if ($bn) {
                        // Beatmap name found, print beatmap name and score
                        echo '<tr>';
                        echo '<td class="success"><p class="text-left">'.current($bn).' <b>'.getScoreMods($recentPlays[$i]['mods']).'</b><br><small>'.timeDifference(time(), osuDateToUNIXTimestamp($recentPlays[$i]['time'])).'</small>'.'</p></td>';
                        echo '<td class="success"><p class="text-right">'.accuracy($recentPlays[$i]['accuracy']).'%</p></td>';
                        echo '<td class="success"><p class="text-right"><b>'.number_format($recentPlays[$i]['score']).'</b></p></td>';
                        echo '</tr>';
                    }
                }
                echo '</table>';
            }

            // Silence thing
            if ($silenceEndTime - time() > 0) {
                echo "<div class='alert alert-danger'><i class='fa fa-exclamation-triangle'></i>	<b>".$username."'s account is not in good standing!</b><br><br><b>This user has been silenced for the following reason:</b><br><i>".$silenceReason.'</i><br><br><b>Silence ends in:</b><br><i>'.timeDifference($silenceEndTime, time(), false).'</i></div>';
            }

            echo '</div>';
        } catch (Exception $e) {
            echo '<br><div class="alert alert-danger" role="alert"><i class="fa fa-exclamation-triangle"></i>	<b>'.$e->getMessage().'</b></div>';
        }
    }

    /*
    * BetaKeys
    * Prints the beta keys page.
    */
    public static function BetaKeys()
    {
        // Maintenance check
        self::MaintenanceStuff();

        // Global alert
        self::GlobalAlert();

        // Title and alerts
        echo '<p align="center"><h1><i class="fa fa-key"></i>	Beta Keys</h1>';

        // Actual User CP
        echo 'Here you can find some Beta keys.<br>You can\'t find a valid beta key? Don\'t worry, we add new ones periodically.<br></p>';
        $betaKeys = $GLOBALS['db']->fetchAll('SELECT description,allowed FROM beta_keys WHERE public = 1 ORDER BY allowed DESC');

        if ($betaKeys) {
            // Print table header
            echo "<table class='table table-hover'>
			<thead>
			<tr>
			<th><p class='text-center'>Beta key</p></th>
			<th><p class='text-center'>Status</p></th>
			</tr>
			</thead>
			<tbody>";

            // Print table content
            foreach ($betaKeys as $key) {
                if ($key['allowed'] == 1) {
                    $icon = 'check';
                    $row = 'success';
                } else {
                    $icon = 'exclamation';
                    $row = 'danger';
                }
                echo "<tr class='".$row."'><td><p class='text-center'><b>".$key['description']."</b></p></td><td><p class='text-center'><i class='fa fa-".$icon."'></i></p></td></tr>";
            }

            // Print table end
            echo '</tbody></table>';
        } else {
            echo '<b>No beta keys available. Try again later.</b>';
        }
    }

    /*
    * AboutPage
    * Prints the about page.
    */
    public static function AboutPage()
    {
        // Maintenance check
        self::MaintenanceStuff();

        // Global alert
        self::GlobalAlert();
        echo file_get_contents('./html_static/about.html');
    }

    /*
    * RulesPage
    * Prints the rules page.
    */
    public static function RulesPage()
    {
        // Maintenance check
        self::MaintenanceStuff();

        // Global alert
        self::GlobalAlert();
        echo file_get_contents('./html_static/rules.html');
    }

    /*
    * ChangelogPage
    * Prints the Changelog page.
    */
    public static function Changelogpage()
    {
        // Maintenance check
        self::MaintenanceStuff();

        // Global alert
        self::GlobalAlert();

        // Changelog
        getChangelog();
    }

    /*
    * ReportPage
    * Prints the Bug report/feature request page.
    */
    public static function ReportPage()
    {
        // Maintenance check
        self::MaintenanceStuff();

        // Global alert
        self::GlobalAlert();

        // Print Exception if set and valid
        $exceptions = ['Nice troll.'];

        if (isset($_GET['e']) && isset($exceptions[$_GET['e']])) {
            self::ExceptionMessage($exceptions[$_GET['e']]);
        }

        // Print Success if set
        if (isset($_GET['s']) && $_GET['s'] === 'ok') {
            self::SuccessMessage("Report send! Thank you for your contribute! We'll try to reply to your report as soon as possible. <b>Check out <a href='index.php?p=24'>this</a> page to get future updates.</b>");
        }

        // Selected thing (for automatic bug report or feature request)
        $selected[0] = '';
        $selected[1] = '';
        if (isset($_GET['type']) && $_GET['type'] <= 1) {
            $selected[$_GET['type']] = 'selected';
        }

        // Changelog
        echo '<div id="narrow-content"><h1><i class="fa fa-paper-plane"></i>	Send a report</h1>Here you can report bugs or request features. Please try to descbibe your bug/feature as detailed as possible.<br>The whole team speaks English and Italian. Don\'t be afraid to write in a maccaroni language!<br><br>';
        echo '<form method="POST" action="submit.php" id="send-report-form">
		<input name="action" value="sendReport" hidden>
		<div class="input-group" style="width:100%">
			<span class="input-group-addon" id="basic-addon1" style="width:40%">Type</span>
			<select name="t" class="selectpicker" data-width="100%" onchange="changeTitlePlaceholder()">
				<option value="0" '.$selected[0].'>Bug report</option>
				<option value="1" '.$selected[1].'>Feature request</option>
			</select>
		</div>

		<p style="line-height: 15px"></p>

		<div class="input-group" style="width:100%">
			<span class="input-group-addon" id="basic-addon1" style="width:40%">Title</span>
			<input name="n" type="text" class="form-control" placeholder="" maxlength="128" required></input>
		</div>

		<p style="line-height: 15px"></p>

		<div class="input-group" style="width:100%">
			<span class="input-group-addon" id="basic-addon1" style="width:40%">Report</span>
			<textarea name="c" class="form-control" placeholder="Main content here. Max 1024 characters" maxlength="1024" style="overflow:auto;resize:vertical;height:100px" required></textarea>
		</div>

		<p style="line-height: 15px"></p>

		<div class="text-center"><button type="submit" form="send-report-form" class="btn btn-primary">Send</button></div><br><br>
		</form>';
        echo '</div>';
    }

    /*
    * ExceptionMessage
    * Display an error alert with a custom message.
    *
    * @param (string) ($e) The custom message (exception) to display.
    */
    public static function ExceptionMessage($e, $ret = false)
    {
        $p = '<div class="alert alert-danger" role="alert"><p align="center"><b>Something bad happened!<br></b> <i>'.$e.'</p></i></div>';
        if ($ret) {
            return $p;
        }
        echo $p;
    }

    /*
    * SuccessMessage
    * Display a success alert with a custom message.
    *
    * @param (string) ($s) The custom message to display.
    */
    public static function SuccessMessage($s, $ret = false)
    {
        $p = '<div class="alert alert-success" role="alert"><p align="center">'.$s.'</p></i></div>';
        if ($ret) {
            return $p;
        }
        echo $p;
    }

    /*
    * LoggedInAlert
    * Display a message to the user that he's already logged in.
    * Printed when a logged in user tries to view a guest only page.
    */
    public static function LoggedInAlert()
    {
        echo '<div class="alert alert-warning" role="alert">You are already logged in.</i></div>';
    }

    /*
    * RegisterPage
    * Prints the register page.
    */
    public static function RegisterPage()
    {
        // Maintenance check
        self::MaintenanceStuff();

        // Global alert
        self::GlobalAlert();

        // Registration enabled check
        if (!checkRegistrationsEnabled()) {
            // Registrations are disabled
            self::ExceptionMessage('<b>Registrations are currently disabled.</b>');
            die();
        }

        echo '<br><div id="narrow-content"><h1><i class="fa fa-plus-circle"></i>	Sign up</h1>';

        // Print Exception if set and valid
        $exceptions = ['Nice troll.', 'Please get your shit together and make a better password', "barney is a dinosaur your password doesn't maaatch!", "D'ya know? your password is dumb. it's also one of the most used around the entire internet. yup.", "The email isn't valid.", "Please write a username that respects osu!'s username criteria.", 'That username was already found in the database! Perhaps someone stole it from you? Those bastards!', 'That email was already found in the database!', 'Invalid beta key.', 'Username now allowed. Please choose another one.'];
        if (isset($_GET['e']) && isset($exceptions[$_GET['e']])) {
            self::ExceptionMessage($exceptions[$_GET['e']]);
        }

        // Print Success if set
        if (isset($_GET['s']) && $_GET['s'] === 'lmao') {
            self::SuccessMessage("You should now be signed up! Try to <a href='index.php?p=2'>login</a>.");
        }

        // Print default warning message if we have no exception/success
        if (!isset($_GET['e']) && !isset($_GET['s'])) {
            echo '<p>Please fill every field in order to sign up.<br>
		<div class="alert alert-danger animated shake" role="alert"><b><i class="fa fa-gavel"></i>	Please read the <a href="index.php?p=23" target="_blank">rules</a> before creating an account.</b></div>
		<a href="index.php?p=16&id=1" target="_blank">Need some help?</a></p>';
        }

        // Print register form
        echo '	<form action="submit.php" method="POST">
		<input name="action" value="register" hidden>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-user" max-width="25%"></span></span><input type="text" name="u" required class="form-control" placeholder="Username" aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-lock" max-width="25%"></span></span><input type="password" name="p1" required class="form-control" placeholder="Password" aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-lock" max-width="25%"></span></span><input type="password" name="p2" required class="form-control" placeholder="Repeat Password" aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-envelope" max-width="25%"></span></span><input type="text" name="e" required class="form-control" placeholder="Email" aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-gift" max-width="25%"></span></span><input type="text" name="k" required class="form-control" placeholder="Beta Key" aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
		<button type="submit" class="btn btn-primary">Sign up!</button>
		</form></div>
		';
    }

    /*
    * ChangePasswordPage
    * Prints the change password page.
    */
    public static function ChangePasswordPage()
    {
        // Maintenance check
        self::MaintenanceStuff();

        // Global alert
        self::GlobalAlert();

        echo '<div id="narrow-content"><h1><i class="fa fa-lock"></i>	Change password</h1>';

        // Print Exception if set
        $exceptions = ['Nice troll.', 'Please get your shit together and make a better password.', "barney is a dinosaur your password doesn't maaatch!", "D'ya know? your password is dumb. it's also one of the most used around the entire internet. yup.", 'Current password is not correct.'];
        if (isset($_GET['e']) && isset($exceptions[$_GET['e']])) {
            self::ExceptionMessage($exceptions[$_GET['e']]);
        }

        // Print Success if set
        if (isset($_GET['s']) && $_GET['s'] == 'done') {
            self::SuccessMessage('Password changed!');
        }

        // Print default message if we have no exception/success
        if (!isset($_GET['e']) && !isset($_GET['s'])) {
            echo '<p>Fill every field with the correct informations in order to change your password.</p>';
        }

        // Print change password form
        echo '<form action="submit.php" method="POST">
		<input name="action" value="changePassword" hidden>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-lock" max-width="25%"></span></span><input type="password" name="pold" required class="form-control" placeholder="Current password" aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-lock" max-width="25%"></span></span><input type="password" name="p1" required class="form-control" placeholder="New password" aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-lock" max-width="25%"></span></span><input type="password" name="p2" required class="form-control" placeholder="Repeat new password" aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
		<button type="submit" class="btn btn-primary">Change password</button>
		</form>
		</div>';
    }

    /*
    * userSettingsPage
    * Prints the user settings page.
    */
    public static function userSettingsPage()
    {
        global $PlayStyleEnum;

        // Maintenance check
        self::MaintenanceStuff();

        // Global alert
        self::GlobalAlert();

        // Get user settings data
        $data = $GLOBALS['db']->fetch('SELECT * FROM users_stats WHERE username = ?', $_SESSION['username']);

        // Title
        echo '<div id="narrow-content"><h1><i class="fa fa-cog"></i>	User settings</h1>';

        // Print Exception if set
        $exceptions = ['Nice troll.'];
        if (isset($_GET['e']) && isset($exceptions[$_GET['e']])) {
            self::ExceptionMessage($exceptions[$_GET['e']]);
        }

        // Print Success if set
        if (isset($_GET['s']) && $_GET['s'] == 'ok') {
            self::SuccessMessage('User settings saved!');
        }

        // Print default message if we have no exception/success
        if (!isset($_GET['e']) && !isset($_GET['s'])) {
            echo '<p>Here you can edit your account settings</p>';
        }

        // Default select stuff
        $selected[0] = [0 => '', 1 => ''];
        $selected[1] = [0 => '', 1 => ''];

        // Howl is cool so he does it in his own way
        $mode = $data['favourite_mode'];
        $cj = function ($index) use ($mode) {
            $r = "value='$index'";
            if ($index == $mode) {
                return $r.' selected';
            }

            return $r.'';
        };

        // Selected stuff
        if ($data['show_country'] == 1) {
            $selected[0][1] = 'selected';
        } else {
            $selected[0][0] = 'selected';
        }
        if (isset($_COOKIE['st']) && $_COOKIE['st'] == 1) {
            $selected[1][1] = 'selected';
        } else {
            $selected[1][0] = 'selected';
        }

        // Print form
        echo '<form action="submit.php" method="POST">
		<input name="action" value="saveUserSettings" hidden>
		<div class="input-group" style="width:100%">
			<span class="input-group-addon" id="basic-addon0" style="width:40%">Show country flag</span>
			<select name="f" class="selectpicker" data-width="100%">
				<option value="1" '.$selected[0][1].'>Yes</option>
				<option value="0" '.$selected[0][0].'>No</option>
			</select>
		</div>
		<p style="line-height: 15px"></p>
		<div class="input-group" style="width:100%">
			<span class="input-group-addon" id="basic-addon1" style="width:40%">Safe page title</span>
			<select name="st" class="selectpicker" data-width="100%">
				<option value="1" '.$selected[1][1].'>Yes</option>
				<option value="0" '.$selected[1][0].'>No</option>
			</select>
		</div>
		<p style="line-height: 15px"></p>
		<div class="input-group" style="width:100%">
			<span class="input-group-addon" id="basic-addon4" style="width:40%">Favourite gamemode</span>
			<select name="mode" class="selectpicker" data-width="100%">
				<option '.$cj(0).'>osu! Standard</option>
				<option '.$cj(1).'>Taiko</option>
				<option '.$cj(2).'>Catch the Beat</option>
				<option '.$cj(3).'>osu!mania</option>
			</select>
		</div>
		<p style="line-height: 15px"></p>
		<div class="input-group" style="width:100%">
			<span class="input-group-addon" id="basic-addon2" style="width:40%">Username color</span>
			<input type="text" name="c" class="form-control colorpicker" value="'.$data['user_color'].'" placeholder="HEX/Html color" aria-describedby="basic-addon2" spellcheck="false">
		</div>
		<p style="line-height: 15px"></p>
		<div class="input-group" style="width:100%">
			<span class="input-group-addon" id="basic-addon3" style="width:40%">A.K.A</span>
			<input type="text" name="aka" class="form-control" value="'.htmlspecialchars($data['username_aka']).'" placeholder="Alternative username (not for login)" aria-describedby="basic-addon3" spellcheck="false">
		</div>
		<p style="line-height: 15px"></p>
		<h3>Playstyle</h3>
		<div style="text-align: left">
		';
        // Display playstyle checkboxes
        $playstyle = $data['play_style'];
        foreach ($PlayStyleEnum as $k => $v) {
            echo "<br>
			<input type='checkbox' name='ps_$k' value='1' ".($playstyle & $v ? 'checked' : '')."> $k";
        }
        echo '
		</div>
		<p style="line-height: 15px"></p>
		<button type="submit" class="btn btn-primary">Save settings</button>
		</form>
		</div>';
    }

    /*
    * ChangeAvatarPage
    * Prints the change avatar page.
    */
    public static function ChangeAvatarPage()
    {
        // Maintenance check
        self::MaintenanceStuff();

        // Global alert
        self::GlobalAlert();

        // Title
        echo '<div id="narrow-content"><h1><i class="fa fa-picture-o"></i>	Change avatar</h1>';

        // Print Exception if set
        $exceptions = ['Nice troll.', 'That file is not a valid image.', 'Invalid file format. Supported extensions are .png, .jpg and .jpeg', 'The file is too large. Maximum file size is 1MB.', 'Error while uploading avatar.'];
        if (isset($_GET['e']) && isset($exceptions[$_GET['e']])) {
            self::ExceptionMessage($exceptions[$_GET['e']]);
        }

        // Print Success if set
        if (isset($_GET['s']) && $_GET['s'] == 'ok') {
            self::SuccessMessage('Avatar changed!');
        }

        // Print default message if we have no exception/success
        if (!isset($_GET['e']) && !isset($_GET['s'])) {
            echo '<p>Give a nice touch to your profile with a custom avatar!<br></p>';
        }

        // Print form
        echo '
		<b>Current avatar:</b><br><img src="'.URL::Avatar().'/'.getUserID($_SESSION['username']).'" height="100" width="100"/>
		<p style="line-height: 15px"></p>
		<form action="submit.php" method="POST" enctype="multipart/form-data">
		<input name="action" value="changeAvatar" hidden>
		<p align="center"><input type="file" name="file"></p>
		<i>Max size: 1MB<br>
		.jpg, .jpeg or <b>.png (reccommended)</b><br>
		Recommended size: 100x100</i>
		<p style="line-height: 15px"></p>
		<button type="submit" class="btn btn-primary">Change avatar</button>
		</form>
		</div>';
    }

    /*
    * UserpageEditorPage
    * Prints the userpage editor page.
    */
    public static function UserpageEditorPage()
    {
        // Maintenance check
        self::MaintenanceStuff();

        // Global alert
        self::GlobalAlert();

        // Get userpage content from db
        $content = $GLOBALS['db']->fetch('SELECT userpage_content FROM users_stats WHERE username = ?', $_SESSION['username']);
        $userpageContent = htmlspecialchars(current(($content === false ? ['t' => ''] : $content)));

        // Title
        echo '<h1><i class="fa fa-pencil"></i>	Userpage</h1>';

        // Print Exception if set
        $exceptions = ['Nice troll.', "Your userpage <b>can't be longer than 1500 characters</b> (bb code syntax included)"];
        if (isset($_GET['e']) && isset($exceptions[$_GET['e']])) {
            self::ExceptionMessage($exceptions[$_GET['e']]);
        }

        // Print Success if set
        if (isset($_GET['s']) && $_GET['s'] == 'ok') {
            self::SuccessMessage('Userpage saved!');
        }

        // Print default message if we have no exception/success
        if (!isset($_GET['e']) && !isset($_GET['s'])) {
            echo '<p>Introduce yourself here! <i>(max 1500 chars)</i></p>';
        }

        // Print form
        echo '<form action="submit.php" method="POST">
		<input name="action" value="saveUserpage" hidden>
		<p align="center"><textarea name="c" class="sceditor" style="width:700px; height:400px;">'.$userpageContent.'</textarea></p>
		<p style="line-height: 15px"></p>
		<button type="submit" class="btn btn-primary">Save userpage</button>
		<a href="index.php?u='.getUserID($_SESSION['username']).'" class="btn btn-success">View userpage</a>
		</form>
		';
    }

    /*
    * PasswordRecovery - print the page to recover your password if you lost it.
    */
    public static function PasswordRecovery()
    {
        // Maintenance check
        self::MaintenanceStuff();

        // Global alert
        self::GlobalAlert();

        echo '<div id="narrow-content" style="width:500px"><h1><i class="fa fa-exclamation-circle"></i> Recover your password</h1>';

        // Print Exception if set and in array.
        $exceptions = [
            'Nice troll.',
            "That user doesn't exist.",
            "You are banned from Ripple. We won't let you come back in.",
        ];
        if (isset($_GET['e']) && isset($exceptions[$_GET['e']])) {
            self::ExceptionMessage($exceptions[$_GET['e']]);
        }
        if (isset($_GET['s'])) {
            self::SuccessMessage('You should have received an email containing instructions on how to recover your Ripple account.');
        }
        if (checkLoggedIn()) {
            echo 'What are you doing here? You\'re already logged in, you moron!<br>';
            echo 'If you really want to fake that you\'ve lost your password, you should at the very least log out of Ripple, you know.';
        } else {
            echo '<p>Let\'s get some things straight. We can only help you if you DID put your actual email address when you signed up. If you didn\'t, you\'re fucked. Hope to know the admins well enough to tell them to change the password for you, otherwise your account is now dead.</p><br>
			<form action="submit.php" method="POST">
			<input name="action" value="recoverPassword" hidden>
			<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="fa fa-user" max-width="25%"></span></span><input type="text" name="username" required class="form-control" placeholder="Type your username." aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
			<button type="submit" class="btn btn-primary">Recover my password!</button>
			</form></div>';
        }
    }

    /*
    * Alerts
    * Print the alerts for the logged in user.
    */
    public static function Alerts()
    {
        // Account activation alert (not implemented yet)
        if (getUserAllowed($_SESSION['username']) == 2) {
            echo '<div class="alert alert-warning" role="alert">To avoid using accounts that you don\'t own, you need to <b>confirm your Ripple account</b>. To do so, simply <b>open your osu! client, login to ripple server and submit a score.</b> Every score is ok, even on unranked maps. <u><b>Remember that if you don\'t activate your Ripple account within 3 hours, it\'ll be deleted!</b></u></div>';
        }

        // Documentation alert to help new users
        if (getUserID($_SESSION['username']) == 2) {
            echo '<div class="alert alert-warning" role="alert">If you are having troubles while activating your account or connecting to Ripple, please check the Documentation section by clicking <a href="index.php?p=14">here</a>.</div>';
        }

        // Country flag alert (only for not pending users)
        if (getUserAllowed($_SESSION['username']) != 2 && current($GLOBALS['db']->fetch('SELECT country FROM users_stats WHERE username = ?', $_SESSION['username'])) == 'XX') {
            echo '<div class="alert alert-warning" role="alert"><b>You don\'t have a country flag.</b> Send a score (even a failed/retried one is fine) to get your country flag.</div>';
        }

        // Other alerts (such as maintenance, ip change and stuff) will be added here
    }

    /*
    * MaintenanceAlert
    * Prints the maintenance alert and die if we are normal users
    * Prints the maintenance alert and keep printing the page if we are mod/admin
    */
    public static function MaintenanceAlert()
    {
        try {
            // Check if we are logged in
            if (!checkLoggedIn()) {
                throw new Exception();
            }

            // Check our rank
            if (getUserRank($_SESSION['username']) < 3) {
                throw new Exception();
            }

            // Mod/admin, show alert and continue
            echo '<div class="alert alert-warning" role="alert"><p align="center"><i class="fa fa-cog fa-spin"></i>	Ripple\'s website is in <b>maintenance mode</b>. Only mods and admins have access to the full website.</p></div>';
        } catch (Exception $e) {
            // Normal user, show alert and die
            echo '<div class="alert alert-warning" role="alert"><p align="center"><i class="fa fa-cog fa-spin"></i>	Ripple\'s website is in <b>maintenance mode</b>. We are working for you, <b>please come back later.</b></p></div>';
            die();
        }
    }

    /*
    * GameMaintenanceAlert
    * Prints the game maintenance alert
    */
    public static function GameMaintenanceAlert()
    {
        try {
            // Check if we are logged in
            if (!checkLoggedIn()) {
                throw new Exception();
            }

            // Check our rank
            if (getUserRank($_SESSION['username']) < 3) {
                throw new Exception();
            }

            // Mod/admin, show alert and continue
            echo '<div class="alert alert-danger" role="alert"><p align="center"><i class="fa fa-cog fa-spin"></i>	Ripple\'s score system is in <b>maintenance mode</b>. <u>Your scores won\'t be saved until maintenance ends.</u><br><b>Make sure to disable game maintenance mode from admin cp as soon as possible!</b></p></div>';
        } catch (Exception $e) {
            // Normal user, show alert and die
            echo '<div class="alert alert-danger" role="alert"><p align="center"><i class="fa fa-cog fa-spin"></i>	Ripple\'s score system is in <b>maintenance mode</b>. <u>Your scores won\'t be saved until maintenance ends.</u></b></p></div>';
        }
    }

    /*
    * BanchoMaintenance
    * Prints the game maintenance alert
    */
    public static function BanchoMaintenanceAlert()
    {
        try {
            // Check if we are logged in
            if (!checkLoggedIn()) {
                throw new Exception();
            }

            // Check our rank
            if (getUserRank($_SESSION['username']) < 3) {
                throw new Exception();
            }

            // Mod/admin, show alert and continue
            echo '<div class="alert alert-danger" role="alert"><p align="center"><i class="fa fa-server"></i>	Ripple\'s Bancho server is in maintenance mode. You can\'t play Ripple right now. Try again later.<br><b>Make sure to disable game maintenance mode from admin cp as soon as possible!</b></p></div>';
        } catch (Exception $e) {
            // Normal user, show alert and die
            echo '<div class="alert alert-danger" role="alert"><p align="center"><i class="fa fa-server"></i>	Ripple\'s Bancho server is in maintenance mode. You can\'t play Ripple right now. Try again later.</p></div>';
        }
    }

    /*
    * MaintenanceStuff
    * Prints website/game maintenance alerts
    */
    public static function MaintenanceStuff()
    {
        // Check Bancho maintenance
        if (checkBanchoMaintenance()) {
            self::BanchoMaintenanceAlert();
        }

        // Game maintenance check
        if (checkGameMaintenance()) {
            self::GameMaintenanceAlert();
        }

        // Check website maintenance
        if (checkWebsiteMaintenance()) {
            self::MaintenanceAlert();
        }
    }

    /*
    * GlobalAlert
    * Prints the global alert (only if not empty)
    */
    public static function GlobalAlert()
    {
        $m = current($GLOBALS['db']->fetch("SELECT value_string FROM system_settings WHERE name = 'website_global_alert'"));

        if ($m != '') {
            echo '<div class="alert alert-warning" role="alert"><p align="center">'.$m.'</p></div>';
        }
    }

    /*
    * HomeAlert
    * Prints the home alert (only if not empty)
    */
    public static function HomeAlert()
    {
        $m = current($GLOBALS['db']->fetch("SELECT value_string FROM system_settings WHERE name = 'website_home_alert'"));

        if ($m != '') {
            echo '<div class="alert alert-warning" role="alert"><p align="center">'.$m.'</p></div>';
        }
    }

    /*
    * MyReportsPage
    * Prints the user settings page.
    */
    public static function MyReportsPage()
    {
        // Maintenance check
        self::MaintenanceStuff();

        // Global alert
        self::GlobalAlert();

        // Get user reports
        $reports = $GLOBALS['db']->fetchAll('SELECT * FROM reports WHERE from_username = ? ORDER BY id DESC', $_SESSION['username']);

        // Title
        echo '<h1><i class="fa fa-paper-plane"></i>	My reports</h1>';

        // Print Exception if set
        $exceptions = ['Invalid report'];
        if (isset($_GET['e']) && isset($exceptions[$_GET['e']])) {
            self::ExceptionMessage($exceptions[$_GET['e']]);
        }

        // Print default message if we have no exception/success
        if (!isset($_GET['e']) && !isset($_GET['s'])) {
            echo '<p>Here you can view your bug reports and fetaure requests.</p>';
        }

        if (!$reports) {
            echo '<b>You haven\'t sent any bug report or feature request. You can send one <a href="index.php?p=22">here</a>.</b>';
        } else {

            // Reports table
            echo '<table class="table table-striped table-hover table-100-center">
			<thead>
			<tr><th class="text-center">Type</th><th class="text-center">Name</th><th class="text-center">Opened on</th><th class="text-center">Updated on</th><th class="text-center">Status</th><th class="text-center">Action</th></tr>
			</thead>
			<tbody>';

            for ($i = 0; $i < count($reports); $i++) {
                // Set status label color and text
                if ($reports[$i]['status'] == 1) {
                    $statusColor = 'success';
                    $statusText = 'Open';
                } else {
                    $statusColor = 'danger';
                    $statusText = 'Closed';
                }

                // Set type label color and text
                if ($reports[$i]['type'] == 1) {
                    $typeColor = 'success';
                    $typeText = 'Feature';
                } else {
                    $typeColor = 'warning';
                    $typeText = 'Bug';
                }

                // Print row
                echo '<tr>';
                echo '<td><p class="text-center"><span class="label label-'.$typeColor.'">'.$typeText.'</span></p></td>';
                echo '<td><p class="text-center"><b>'.$reports[$i]['name'].'</b></p></td>';
                echo '<td><p class="text-center">'.date('d/m/Y H:i:s', intval($reports[$i]['open_time'])).'</p></td>';
                echo '<td><p class="text-center">'.date('d/m/Y H:i:s', intval($reports[$i]['update_time'])).'</p></td>';
                echo '<td><p class="text-center"><span class="label label-'.$statusColor.'">'.$statusText.'</span></p></td>';

                // Edit button
                echo '
				<td><p class="text-center">
				<a class="btn btn-xs btn-primary" href="index.php?p=25&id='.$reports[$i]['id'].'"><span class="glyphicon glyphicon-eye-open"></span></a>
				</p></td>';

                // End row
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
    }

    /*
    * MyReportViewPage
    * Prints the my report view page.
    */
    public static function MyReportViewPage()
    {
        // Maintenance check
        self::MaintenanceStuff();

        // Global alert
        self::GlobalAlert();

        try {
            // Make sure everything is set
            if (!isset($_GET['id']) || empty($_GET['id'])) {
                throw new Exception(0);
            }

            // Make sure the report exists and it's ours
            $reportData = $GLOBALS['db']->fetch('SELECT * FROM reports WHERE id = ? AND from_username = ?', [$_GET['id'], $_SESSION['username']]);
            if (!$reportData) {
                throw new Exception(0);
            }

            // Title
            echo '<h1><i class="fa fa-paper-plane"></i>	View report</h1>';

            // Report table
            // Set type label color and text
            if ($reportData['type'] == 1) {
                $typeColor = 'success';
                $typeText = 'Feature request';
            } else {
                $typeColor = 'warning';
                $typeText = 'Bug report';
            }

            // Set status label color and text
            if ($reportData['status'] == 1) {
                $statusColor = 'success';
                $statusText = 'Open';
            } else {
                $statusColor = 'danger';
                $statusText = 'Closed';
            }

            if (!empty($reportData['response'])) {
                $response = $reportData['response'];
            } else {
                $response = 'No response yet';
            }

            echo '<table class="table table-striped table-hover table-50-center">';
            echo '<tbody>';
            echo '<tr>
			<td><b>Title</b></td>
			<td><b>'.htmlspecialchars($reportData['name']).'</b></td>
			</tr>';
            echo '<tr>
			<td><b>Type</b></td>
			<td><span class="label label-'.$typeColor.'">'.$typeText.'</span></td>
			</tr>';
            echo '<tr>
			<td><b>Status</b></td>
			<td><span class="label label-'.$statusColor.'">'.$statusText.'</span></td>
			</tr>';
            echo '<tr>
			<td><b>Opened on</b></td>
			<td>'.date('d/m/Y H:i:s', $reportData['open_time']).'</td>
			</tr>';
            echo '<tr>
			<td><b>Updated on</b></td>
			<td>'.date('d/m/Y H:i:s', $reportData['update_time']).'</td>
			</tr>';
            echo '<tr class="success">
			<td><b>Content</b></td>
			<td><i>'.htmlspecialchars($reportData['content']).'</i></td>
			</tr>';
            echo '<tr class="warning">
			<td><b>Response</b></td>
			<td><i>'.htmlspecialchars($response).'</i></td>
			</tr>';
            echo '</tbody>';
            echo '</table>';
        } catch (Exception $e) {
            redirect('index.php?p=24&e='.$e->getMessage());
        }
    }

    /*
    * FriendlistPage
    * Prints the friendlist page.
    */
    public static function FriendlistPage()
    {
        // Maintenance check
        self::MaintenanceStuff();

        // Global alert
        self::GlobalAlert();

        // Get user friends
        $ourID = getUserID($_SESSION['username']);
        $friends = $GLOBALS['db']->fetchAll('SELECT user2 FROM users_relationships WHERE user1 = ?', [$ourID]);

        // Title and header message
        echo '<h1><i class="fa fa-star"></i>	Friendlist</h1>';

        if (count($friends) == 0) {
            echo '<b>You don\'t have any friends.</b> You can add someone to your friendlist<br>by clicking the <b>"Add as friend"</b> on someones\'s profile.<br>You can add friends from the game client too.';
        } else {
            // Friendlist
            echo '<table class="table table-striped table-hover table-50-center">
			<thead>
			<tr><th class="text-center">Username</th><th class="text-center">Mutual</th></tr>
			</thead>
			<tbody>';

            // Loop through every friend and output its username and mutual status
            foreach ($friends as $friend) {
                $uname = getUserUsername($friend['user2']);
                $mutualIcon = ($friend['user2'] == 999 || getFriendship($friend['user2'], $ourID, true) == 2) ? '<i class="fa fa-heart"></i>' : '';
                echo '<tr><td><div align="center"><a href="index.php?u='.$friend['user2'].'">'.$uname.'</a></div></td><td><div align="center">'.$mutualIcon.'</div></td></tr>';
            }

            echo '</tbody></table>';
        }
    }
}
