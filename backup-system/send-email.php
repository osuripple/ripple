<?php
	if(PHP_SAPI == 'cli')
	{
		require_once(dirname(__FILE__) . "/../osu.ppy.sh/inc/SimpleMailgun.php");
		require_once(dirname(__FILE__) . "/../osu.ppy.sh/inc/config.php");
		$mailer = new SimpleMailgun($MailgunConfig);

		$ini = parse_ini_file("config.ini");
		$where = "";
		if ($ini["backblaze_enable"] == 1)
			$where .= "Backblaze, ";
		if ($ini["s3_enable"] == 1)
			$where .= "S3, ";
		if ($ini["local_enable"] == 1)
			$where .= "Local";
		$where = rtrim($where, ", ");

		$type = "";
		if ($ini["backup_database"] == 1)
			$type .= "Database, ";
		if ($ini["backup_replays"] == 1)
			$type .= "Replays";
		$type = rtrim($type, ", ");

		$who = explode(",", $ini["email_to"]);

		foreach ($who as $to) {
			echo("Sending email to ".$to."...\n");
			$mailer->Send(
				"ripple <noreply@ripple.moe>",
				$to,
				"Ripple backup notification",
				"Ripple data has been backed up!<br><br><b>Backup Type:</b> ".$type."<br><b>Backup size:</b> ".$argv[1]."<br><b>Backed up to:</b> ".$where."<br><br>Log in to your Backblaze/S3/OVH account to download the backup archive."
			);
		}
	}
?>
