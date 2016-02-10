<?php
$GLOBALS["db"]->execute("ALTER TABLE `bancho_tokens` ADD `game_mode` TINYINT NOT NULL AFTER `joined_channels`;");
