<?php
$d = file_get_contents(dirname(__FILE__) . "/../migrations/latest.txt");
$d++;
file_put_contents(dirname(__FILE__) . "/../migrations/$d.php", "<?php
// Content goes here...");
