<?php
$GLOBALS["db"]->execute("ALTER TABLE `bancho_tokens` ADD `joined_channels` VARCHAR(512) NOT NULL AFTER `latest_heavy_packet_time`;");
