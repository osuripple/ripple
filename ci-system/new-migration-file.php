<?php

$d = file_get_contents(dirname(__FILE__).'/../migrations/latest.txt');
do {
    $d++;
} while (file_exists(dirname(__FILE__)."/../migrations/$d.php"));
file_put_contents(dirname(__FILE__)."/../migrations/$d.php", '<?php
// Content goes here...');
