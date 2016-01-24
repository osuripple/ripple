ALTER TABLE `users` ADD `silence_end` INT NOT NULL AFTER `latest_activity`;
ALTER TABLE `users` ADD `silence_reason` VARCHAR(127) NOT NULL AFTER `silence_end`;
ALTER TABLE `bancho_tokens` ADD `kicked` TINYINT NOT NULL AFTER `latest_message_id`;

CREATE TABLE `bancho_channels` (
  `id` int(11) NOT NULL,
  `name` varchar(32) NOT NULL,
  `description` varchar(127) NOT NULL,
  `status` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `bancho_channels` (`id`, `name`, `description`, `status`) VALUES
(1, '#osu', '', 1);

ALTER TABLE `bancho_channels`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `bancho_channels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;