<?php

$f = dirname(__FILE__);
require_once $f.'/../osu.ppy.sh/inc/functions.php';

$latest = -1;
if (file_exists($f.'/../migrations/latest.txt')) {
    $latest = trim(file_get_contents($f.'/../migrations/latest.txt'));
}
if (!is_numeric($latest)) {
    $latest = -1;
}

$latest++;
for (true; file_exists($f.'/../migrations/'.$latest.'.php'); $latest++) {
    echo "Migration... $latest.php\n";
    require_once $f.'/../migrations/'.$latest.'.php';
    echo "\nDone migration for $latest.php\n";
}
$latest--;
echo 'End of migration. Current latest update: '.$latest;
echo "\n";
echo "Notifying everyone about the update...\n";
$GLOBALS['db']->execute("INSERT INTO bancho_messages (msg_from, msg_to, msg, time) VALUES (999, '#osu', 'A new Ripple update has been pushed! Click (here)[http://ripple.moe/?p=17] to see the changes.', ?)", [time()]);
file_put_contents($f.'/../migrations/latest.txt', $latest);
