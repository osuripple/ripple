<?php
/*
 * I got no fucking idea of what this does. Seems to be called before a score submission.
 *
 * GET parameters:
 * u - The username
 * p - Password hash
 * c - 'Chart' ID (?)
 *
 * On test, the server responded "ok". Not even microsoft could make something this un-helpful.
 * hey, whoever is reading this. Noticed how I did not know what the fuck p was (which of course is the password)? That's because this was one of the first things ever sniffed from osu!. The "something happened" error meme was still around, too!
 */
require_once dirname(__FILE__) . "/../inc/config.php";
require_once dirname(__FILE__) . "/../inc/db.php";
if (isset($_GET["v"])) {
  echo "9.43";
}
else {
  echo("");
}
