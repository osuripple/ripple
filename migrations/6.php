<?php

echo "Updating passwords...\n";
$GLOBALS['db']->execute('ALTER TABLE users ADD password_version TINYINT(4) NOT NULL DEFAULT 1');
$GLOBALS['db']->execute('ALTER TABLE users MODIFY password_md5 VARCHAR(127) NOT NULL;');
echo "Updating NULL accuracy columns...\n";
foreach (['std', 'taiko', 'ctb', 'mania'] as $v) {
    $GLOBALS['db']->execute("UPDATE users_stats SET avg_accuracy_$v = '0.0' WHERE avg_accuracy_$v = NULL;");
    $GLOBALS['db']->execute("ALTER TABLE users_stats MODIFY avg_accuracy_$v float(15,12) NOT NULL;");
}
