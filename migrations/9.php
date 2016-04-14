<?php

echo "Fixing remember tokens...\n";

$remUsers = $GLOBALS['db']->fetchAll('SELECT * FROM remember');

$new = [];

foreach ($remUsers as $k => $v) {
    $new[] = [
        'id'                => getUserID($v['username']),
        'series_identifier' => $v['series_identifier'],
        'token_sha'         => $v['token_sha'],
    ];
}

$GLOBALS['db']->execute('DROP TABLE remember');
$GLOBALS['db']->execute('CREATE TABLE remember(id INT(11) NOT NULL AUTO_INCREMENT, userid INT(11) NOT NULL, series_identifier INT(11), token_sha VARCHAR(255), PRIMARY KEY(id));');

foreach ($new as $u) {
    echo 'Updating '.$u['id'].'...';
    $GLOBALS['db']->execute('INSERT INTO remember(userid, series_identifier, token_sha) VALUES (?, ?, ?)', $u['id'], $u['series_identifier'], $u['token_sha']);
}
