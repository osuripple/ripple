<?php

class URL {
	static function Avatar() {
		global $URL;
		return (isset($URL["avatar"]) ? $URL["avatar"] : "https://a.ripple.moe");
	}
	static function Server() {
		global $URL;
		return (isset($URL["server"]) ? $URL["server"] : "https://ripple.moe");
	}
}
