<?php
// THE SUPER RIPPLE CONTINUOUS INTEGRATION SYSTEM
// It actually just calls the bash script at the bottom that updates everything locally.
require_once("config.php");
try
{
	if (!isset($_POST["secret"]) || empty($_POST["secret"]) || $_POST["secret"] != SECRET)
		throw new Exception("Invalid secret key");
	
	shell_exec("sh ../ci-system/ci.sh > /tmp/ci.log");
	//shell_exec("/usr/bin/env bash " . dirname(__FILE__) . "/../ci-system/ci.sh /tmp/ci.log 2>>/tmp/ci.log &");
}
catch (Exception $e)
{
	echo($e->getMessage());
}