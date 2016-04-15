<?php
/*
 * Exception stuff for screenshot upload
*/
if (isset($_GET['s']) && !empty($_GET['s'])) {
	switch ($_GET['s']) {
		case 1:
			echo 'Internal error. Contanct a system administrator.';
		break;
		case 2:
			echo 'Please no akerino.';
		break;
		case 3:
			echo 'The image is too big.';
		break;
		case 4:
			echo 'Invalid username/password.';
		break;
		default:
			echo 'Unknown error.';
		break;
	}
}
