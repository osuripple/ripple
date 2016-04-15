<?php
    echo "Updating scores table for PP...\n";
    $q = <<<'ENDOFMYSQLQUERY'
    ALTER TABLE `scores` ADD `pp` FLOAT NULL DEFAULT '0' AFTER `accuracy`;
    ENDOFMYSQLQUERY;

    $GLOBALS['db']->execute($q);
 ?>
