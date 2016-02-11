<?php
$GLOBALS["db"]->execute("ALTER TABLE `users` ADD `friends` TEXT NOT NULL AFTER `silence_reason`;");
$GLOBALS["db"]->execute("ALTER TABLE `users` CHANGE `friends` `friends` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;");
