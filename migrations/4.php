<?php

echo "Fixing score...\n";
$GLOBALS['db']->execute('ALTER TABLE scores MODIFY score BIGINT(20)');

foreach (['ranked', 'total'] as $p1) {
    foreach (['std', 'taiko', 'ctb', 'mania'] as $p2) {
        echo sprintf("Fixing %s_score_%s\n", $p1, $p2);
        $GLOBALS['db']->execute(sprintf('ALTER TABLE users_stats MODIFY %s_score_%s BIGINT(20)', $p1, $p2));
    }
}
