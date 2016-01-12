<?php
	/*
	 * Exception stuff for screenshot upload
	 */
	if (isset($_GET["s"]) && !empty($_GET["s"]))
	{
		switch($_GET["s"])
		{
			case "empty": echo("Internal error. Contanct a system administrator."); break;
			case "pass": echo("Invalid username/password."); break;
			default: echo("Unknown error."); break;
		}
	}
?>