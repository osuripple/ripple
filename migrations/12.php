<?php

echo "Updating beatmaps and users_stats tables for PP...\n";
$q = <<<'ENDOFMYSQLQUERY'
ALTER TABLE `beatmaps` ADD `ar` FLOAT NOT NULL DEFAULT '0' AFTER `song_name`, ADD `od` FLOAT NOT NULL DEFAULT '0' AFTER `ar`, ADD `difficulty` FLOAT NOT NULL DEFAULT '0' AFTER `od`, ADD `hit_circles` INT NOT NULL DEFAULT '0' AFTER `difficulty`;
ALTER TABLE `users_stats` ADD `pp_std` FLOAT NOT NULL DEFAULT '0' AFTER `avg_accuracy_mania`, ADD `pp_taiko` FLOAT NOT NULL DEFAULT '0' AFTER `pp_std`, ADD `pp_ctb` FLOAT NOT NULL DEFAULT '0' AFTER `pp_taiko`, ADD `pp_mania` FLOAT NOT NULL DEFAULT '0' AFTER `pp_ctb`;
ALTER TABLE `beatmaps` CHANGE `hit_circles` `max_combo` INT(11) NOT NULL DEFAULT '0';
ALTER TABLE `beatmaps` ADD `hit_length` INT NOT NULL DEFAULT '0' AFTER `max_combo`;
ENDOFMYSQLQUERY;

$GLOBALS['db']->execute($q);
