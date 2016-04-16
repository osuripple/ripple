<?php

class PasswordFinishRecovery {
	const PageID = 19;
	const URL = 'recovery/finish';
	const Title = 'Ripple - Password recovery';
	const LoggedIn = false;
	public $error_messages = ['Nice troll.', 'Please get your shit together and make a better password.', "barney is a dinosaur your password doesn't maaatch!", "D'ya know? your password is dumb. it's also one of the most used around the entire internet. yup.", "Don't even try."];
	public $mh_GET = ['k', 'user'];
	public $mh_POST = ['k', 'user', 'p1', 'p2'];

	public function P() {
		if (!$this->PrintGetData()) {
			P::ExceptionMessage('The user/key pair you provided in the URL is not valid. Which means either the link expired, it was already used, or it was never there in the first place. The latter is most likely to be the case. Again, you should not be here.');

			return;
		}
		echo '<div id="narrow-content" style="width:500px"><h1><i class="fa fa-exclamation-circle"></i> Recover your password</h1>';
		echo sprintf('<p>Glad to have you here again, %s! To finish the password recovery, please type in a new password:</p>', $_GET['user']);
		echo '<form action="submit.php" method="POST">
		<input name="action" value="recovery/finish" hidden>
		<input name="k" value="'.$_GET['k'].'" hidden>
		<input name="user" value="'.$_GET['user'].'" hidden>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-lock" max-width="25%"></span></span><input type="password" name="p1" required class="form-control" placeholder="New password" aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
		<div class="input-group"><span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-lock" max-width="25%"></span></span><input type="password" name="p2" required class="form-control" placeholder="Repeat new password" aria-describedby="basic-addon1"></div><p style="line-height: 15px"></p>
		<button type="submit" class="btn btn-primary">Change password</button>
		</form>
		</div>';
	}

	public function PrintGetData() {
		return $GLOBALS['db']->fetch('SELECT id FROM password_recovery WHERE k = ? AND u = ?;', [$_GET['k'], $_GET['user']]) !== false;
	}

	public function D() {
		redirect($this->DoGetdata());
	}

	public function DoGetdata() {
		try {
			$d = $GLOBALS['db']->fetch('SELECT id FROM password_recovery WHERE k = ? AND u = ?;', [$_POST['k'], $_POST['user']]);
			if ($d === false) {
				throw new Exception(4);
			}
			// Validate password through our helper
			$pres = PasswordHelper::ValidatePassword($_POST['p1'], $_POST['p2']);
			if ($pres !== -1) {
				throw new Exception($pres);
			}
			// Calculate new password
			$newPassword = password_hash(md5($_POST['p1']), PASSWORD_DEFAULT);
			// Change both passwords and salt
			$GLOBALS['db']->execute("UPDATE users SET password_md5 = ?, salt = '', password_version = '2' WHERE username = ?", [$newPassword, $_POST['user']]);
			// Delete password reset key
			$GLOBALS['db']->fetch('DELETE FROM password_recovery WHERE id = ?;', [$d['id']]);
			// Redirect to success page
			return 'index.php?p=2&s=0';
		}
		catch(Exception $e) {
			return 'index.php?p=19&e='.$e->getMessage().'&k='.$_POST['k'].'&user='.$_POST['user'];
		}
	}
}
