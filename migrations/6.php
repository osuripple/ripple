<?php
$GLOBALS["db"]->execute("ALTER TABLE `bancho_tokens` ADD `action_text` VARCHAR(128) NOT NULL AFTER `action`;");
