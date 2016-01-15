/* Update query goes here */
ALTER TABLE `users_stats`
ADD `replays_watched_std` int(11) unsigned NOT NULL DEFAULT '0' AFTER `total_score_std`,
ADD `replays_watched_taiko` int(11) NOT NULL DEFAULT '0' AFTER `total_score_taiko`,
ADD `replays_watched_ctb` int(11) NOT NULL DEFAULT '0' AFTER `total_score_ctb`,
ADD `replays_watched_mania` int unsigned NOT NULL DEFAULT '0' AFTER `total_score_mania`;