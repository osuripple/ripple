ALTER TABLE `bancho_messages` ADD `msg_from_username` VARCHAR(30) NOT NULL AFTER `msg_from`;
ALTER TABLE `bancho_messages` CHANGE `msg_from` `msg_from_userid` INT(11) NOT NULL;