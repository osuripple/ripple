<?php
// Content goes here...
$q = <<<'ENDOFMYSQLQUERY'
DROP TABLE beatmaps;
CREATE TABLE `beatmaps` (
  `id` int(11) NOT NULL,
  `beatmap_id` int(11) NOT NULL DEFAULT '0',
  `beatmapset_id` int(11) NOT NULL DEFAULT '0',
  `beatmap_md5` varchar(32) NOT NULL DEFAULT '',
  `song_name` varchar(128) NOT NULL DEFAULT '',
  `ranked` tinyint(4) NOT NULL DEFAULT '0',
  `latest_update` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
ALTER TABLE `beatmaps`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `beatmaps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ENDOFMYSQLQUERY;

$GLOBALS["db"]->execute($q);
