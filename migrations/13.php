<?php
    echo "Updating scores table for PP...\n";
    $q = "ALTER TABLE `scores` ADD `pp` FLOAT NULL DEFAULT '0' AFTER `accuracy`;";
    $GLOBALS['db']->execute($q);
