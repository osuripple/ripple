<?php
$GLOBALS["db"]->execute("DROP TABLE bancho_channels;");

$GLOBALS["db"]->execute("
CREATE TABLE `bancho_channels` (
  `id` int(11) NOT NULL,
  `name` varchar(32) NOT NULL,
  `description` varchar(127) NOT NULL,
  `public_read` tinyint(4) NOT NULL,
  `public_write` tinyint(4) NOT NULL,
  `status` tinyint(4) NOT NULL
)
");

$GLOBALS["db"]->execute("
ALTER TABLE `bancho_channels`
  ADD PRIMARY KEY (`id`);");

$GLOBALS["db"]->execute("
ALTER TABLE `bancho_channels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
