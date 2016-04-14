<?php

$q = <<<'ENDOFMYSQLQUERY'
CREATE TABLE `badges` (
  `id` int(11) NOT NULL,
  `name` varchar(32) NOT NULL,
  `icon` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `badges` (`id`, `name`, `icon`) VALUES
(0, 'None', ''),
(1, 'Beta tester 1.5', 'fa-gift');

CREATE TABLE `bancho_channels` (
  `id` int(11) NOT NULL,
  `name` varchar(32) NOT NULL,
  `description` varchar(127) NOT NULL,
  `public_read` tinyint(4) NOT NULL,
  `public_write` tinyint(4) NOT NULL,
  `status` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `bancho_channels` (`id`, `name`, `description`, `public_read`, `public_write`, `status`) VALUES
(1, '#osu', 'Main Ripple channel', 1, 1, 1),
(2, '#announce', 'Announce channel', 1, 0, 1),
(3, '#admin', 'Admin only channel', 0, 0, 1),
(4, '#italian', 'Italian community channel', 1, 1, 1),
(5, '#english', 'English speaking channel', 1, 1, 1);

CREATE TABLE `bancho_messages` (
  `id` int(11) NOT NULL,
  `msg_from_userid` int(11) NOT NULL,
  `msg_from_username` varchar(30) NOT NULL,
  `msg_to` varchar(32) NOT NULL,
  `msg` varchar(127) NOT NULL,
  `time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `bancho_private_messages` (
  `id` int(11) NOT NULL,
  `msg_from_userid` int(11) NOT NULL,
  `msg_from_username` varchar(30) NOT NULL,
  `msg_to` varchar(32) NOT NULL,
  `msg` varchar(127) NOT NULL,
  `time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `bancho_settings` (
  `id` int(11) NOT NULL,
  `name` varchar(32) NOT NULL,
  `value_int` int(11) NOT NULL DEFAULT '0',
  `value_string` varchar(512) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `bancho_settings` (`id`, `name`, `value_int`, `value_string`) VALUES
(1, 'bancho_maintenance', 0, ''),
(2, 'free_direct', 1, ''),
(3, 'menu_icon', 0, 'http://y.zxq.co/mpyxts.png|http://ripple.moe'),
(4, 'login_messages', 0, ''),
(5, 'restricted_joke', 0, ''),
(6, 'login_notification', 0, 'Welcome to pep.py!'),
(7, 'osu_versions', 0, ''),
(8, 'osu_md5s', 0, '');

CREATE TABLE `bancho_tokens` (
  `id` int(11) NOT NULL,
  `token` varchar(16) NOT NULL,
  `osu_id` int(11) NOT NULL,
  `latest_message_id` int(11) NOT NULL,
  `latest_private_message_id` int(11) NOT NULL,
  `latest_packet_time` int(11) NOT NULL,
  `latest_heavy_packet_time` int(11) NOT NULL,
  `joined_channels` varchar(512) NOT NULL,
  `game_mode` tinyint(4) NOT NULL,
  `action` int(11) NOT NULL,
  `action_text` varchar(128) NOT NULL,
  `kicked` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `beatmaps` (
  `id` int(11) NOT NULL,
  `beatmap_id` int(11) NOT NULL DEFAULT '0',
  `beatmap_md5` varchar(32) NOT NULL DEFAULT '',
  `beatmap_file` varchar(128) NOT NULL DEFAULT '',
  `song_artist` varchar(128) NOT NULL DEFAULT '',
  `song_title` varchar(128) NOT NULL DEFAULT '',
  `ranked` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `beatmaps_names` (
  `id` int(11) NOT NULL,
  `beatmap_md5` varchar(32) NOT NULL DEFAULT '',
  `beatmap_name` varchar(256) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `beta_keys` (
  `id` int(11) NOT NULL,
  `key_md5` varchar(32) NOT NULL DEFAULT '',
  `description` varchar(128) NOT NULL DEFAULT '',
  `allowed` tinyint(4) NOT NULL DEFAULT '0',
  `public` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `beta_keys` (`id`, `key_md5`, `description`, `allowed`, `public`) VALUES
(1, '110fe4a3830966be01d0b513fcdb4f66', 'betakey', 1, 1);

CREATE TABLE `docs` (
  `id` int(11) UNSIGNED NOT NULL,
  `doc_name` varchar(255) NOT NULL DEFAULT 'New Documentation File',
  `doc_contents` mediumtext NOT NULL,
  `public` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `old_name` varchar(200) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `leaderboard_ctb` (
  `position` int(10) UNSIGNED NOT NULL,
  `user` int(11) NOT NULL,
  `v` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `leaderboard_mania` (
  `position` int(10) UNSIGNED NOT NULL,
  `user` int(11) NOT NULL,
  `v` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `leaderboard_std` (
  `position` int(10) UNSIGNED NOT NULL,
  `user` int(11) NOT NULL,
  `v` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `leaderboard_taiko` (
  `position` int(10) UNSIGNED NOT NULL,
  `user` int(11) NOT NULL,
  `v` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `password_recovery` (
  `id` int(11) NOT NULL,
  `k` varchar(80) NOT NULL,
  `u` varchar(30) NOT NULL,
  `t` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `remember` (
  `username` varchar(30) NOT NULL,
  `series_identifier` int(10) UNSIGNED NOT NULL,
  `token_sha` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `from_username` varchar(32) NOT NULL,
  `content` varchar(1024) NOT NULL,
  `type` tinyint(4) NOT NULL,
  `open_time` varchar(18) NOT NULL,
  `update_time` varchar(18) NOT NULL,
  `status` tinyint(4) NOT NULL,
  `response` varchar(1024) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `scores` (
  `id` int(11) NOT NULL,
  `beatmap_md5` varchar(32) NOT NULL DEFAULT '',
  `username` varchar(30) NOT NULL DEFAULT '',
  `score` int(11) NOT NULL DEFAULT '0',
  `max_combo` int(11) NOT NULL DEFAULT '0',
  `full_combo` tinyint(1) NOT NULL DEFAULT '0',
  `mods` int(11) NOT NULL DEFAULT '0',
  `300_count` int(11) NOT NULL DEFAULT '0',
  `100_count` int(11) NOT NULL DEFAULT '0',
  `50_count` int(11) NOT NULL DEFAULT '0',
  `katus_count` int(11) NOT NULL DEFAULT '0',
  `gekis_count` int(11) NOT NULL DEFAULT '0',
  `misses_count` int(11) NOT NULL DEFAULT '0',
  `time` varchar(18) NOT NULL DEFAULT '',
  `play_mode` tinyint(4) NOT NULL DEFAULT '0',
  `completed` tinyint(11) NOT NULL DEFAULT '0',
  `accuracy` float(15,12) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `name` varchar(32) NOT NULL,
  `value_int` int(11) NOT NULL DEFAULT '0',
  `value_string` varchar(512) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `system_settings` (`id`, `name`, `value_int`, `value_string`) VALUES
(1, 'website_maintenance', 0, ''),
(2, 'game_maintenance', 0, ''),
(3, 'website_global_alert', 0, ''),
(4, 'website_home_alert', 0, ''),
(5, 'registrations_enabled', 1, '');

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `osu_id` int(11) NOT NULL DEFAULT '2',
  `username` varchar(30) NOT NULL,
  `password_md5` varchar(32) NOT NULL,
  `salt` varchar(32) NOT NULL,
  `email` varchar(254) NOT NULL,
  `register_datetime` int(10) NOT NULL,
  `rank` tinyint(1) NOT NULL DEFAULT '1',
  `allowed` tinyint(1) NOT NULL,
  `latest_activity` int(10) NOT NULL DEFAULT '0',
  `silence_end` int(11) NOT NULL,
  `silence_reason` varchar(127) NOT NULL,
  `friends` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `users_stats` (
  `id` int(11) NOT NULL,
  `osu_id` int(11) NOT NULL,
  `username` varchar(30) NOT NULL,
  `username_aka` varchar(32) NOT NULL,
  `user_color` varchar(16) NOT NULL DEFAULT 'black',
  `user_style` varchar(128) NOT NULL DEFAULT '',
  `ranked_score_std` int(11) NOT NULL DEFAULT '0',
  `playcount_std` int(11) NOT NULL DEFAULT '0',
  `total_score_std` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `replays_watched_std` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `ranked_score_taiko` int(11) NOT NULL DEFAULT '0',
  `playcount_taiko` int(11) NOT NULL DEFAULT '0',
  `total_score_taiko` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `replays_watched_taiko` int(11) NOT NULL DEFAULT '0',
  `ranked_score_ctb` int(11) NOT NULL DEFAULT '0',
  `playcount_ctb` int(11) NOT NULL DEFAULT '0',
  `total_score_ctb` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `replays_watched_ctb` int(11) NOT NULL DEFAULT '0',
  `ranked_score_mania` int(11) NOT NULL DEFAULT '0',
  `playcount_mania` int(11) NOT NULL DEFAULT '0',
  `total_score_mania` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `replays_watched_mania` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `total_hits_std` int(11) NOT NULL DEFAULT '0',
  `total_hits_taiko` int(11) NOT NULL DEFAULT '0',
  `total_hits_ctb` int(11) NOT NULL DEFAULT '0',
  `total_hits_mania` int(11) NOT NULL DEFAULT '0',
  `country` char(2) NOT NULL DEFAULT 'XX',
  `show_country` tinyint(4) NOT NULL DEFAULT '1',
  `level_std` int(11) NOT NULL DEFAULT '1',
  `level_taiko` int(11) NOT NULL DEFAULT '1',
  `level_ctb` int(11) NOT NULL DEFAULT '1',
  `level_mania` int(11) NOT NULL DEFAULT '1',
  `avg_accuracy_std` float(15,12) DEFAULT NULL,
  `avg_accuracy_taiko` float(15,12) DEFAULT NULL,
  `avg_accuracy_ctb` float(15,12) DEFAULT NULL,
  `avg_accuracy_mania` float(15,12) DEFAULT NULL,
  `badges_shown` varchar(24) NOT NULL DEFAULT '1,0,0,0,0,0',
  `safe_title` tinyint(4) NOT NULL DEFAULT '0',
  `userpage_content` mediumtext NOT NULL,
  `play_style` smallint(6) NOT NULL DEFAULT '0',
  `favourite_mode` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


ALTER TABLE `badges`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `bancho_channels`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `bancho_messages`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `bancho_private_messages`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `bancho_settings`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `bancho_tokens`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `beatmaps`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `beatmaps_names`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `beta_keys`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `docs`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `leaderboard_ctb`
  ADD PRIMARY KEY (`position`);

ALTER TABLE `leaderboard_mania`
  ADD PRIMARY KEY (`position`);

ALTER TABLE `leaderboard_std`
  ADD PRIMARY KEY (`position`);

ALTER TABLE `leaderboard_taiko`
  ADD PRIMARY KEY (`position`);

ALTER TABLE `password_recovery`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `remember`
  ADD PRIMARY KEY (`series_identifier`);

ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `scores`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `users_stats`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `bancho_channels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
ALTER TABLE `bancho_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `bancho_private_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `bancho_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
ALTER TABLE `bancho_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `beatmaps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `beatmaps_names`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `beta_keys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `docs`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `password_recovery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `scores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `users_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ENDOFMYSQLQUERY;

$GLOBALS['db']->execute($q);
