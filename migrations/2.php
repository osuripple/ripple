<?php
$GLOBALS["db"]->execute("
CREATE TABLE `bancho_private_messages` (
  `id` int(11) NOT NULL,
  `msg_from_userid` int(11) NOT NULL,
  `msg_from_username` varchar(30) NOT NULL,
  `msg_to` varchar(32) NOT NULL,
  `msg` varchar(127) NOT NULL,
  `time` int(11) NOT NULL
);
");

$GLOBALS["db"]->execute("
ALTER TABLE `bancho_private_messages` ADD PRIMARY KEY (`id`);
");

$GLOBALS["db"]->execute("
ALTER TABLE `bancho_tokens` ADD `latest_private_message_id` INT NOT NULL AFTER `latest_message_id`;
");

$GLOBALS["db"]->execute("
ALTER TABLE `bancho_private_messages` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT;
");
