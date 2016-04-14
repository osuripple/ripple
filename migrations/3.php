<?php

$GLOBALS['db']->execute('CREATE TABLE users_relationships(
	id int(11) not null auto_increment,
	user1 int(11) not null,
	user2 int(11) not null,
	PRIMARY KEY(id)
)');

$users = $GLOBALS['db']->fetchAll('SELECT username, osu_id, friends FROM users');

echo "Populating user_friendships...\n";
foreach ($users as $u) {
    echo '... for user '.$u['username']."\n";
    $friends = explode(',', $u['friends']);
    foreach ($friends as $friend) {
        if ($friend != 0) {
            $GLOBALS['db']->execute('INSERT INTO users_relationships(user1, user2) VALUES (?, ?);', [$u['osu_id'], $friend]);
        }
    }
}
echo "done.\n";

$GLOBALS['db']->execute('ALTER TABLE users DROP COLUMN friends');
