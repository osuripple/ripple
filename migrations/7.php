<?php

echo "Creating api tokens table...\n";
$GLOBALS['db']->execute('CREATE TABLE `tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` varchar(31) NOT NULL,
  `privileges` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `token` varchar(127) NOT NULL,
  PRIMARY KEY (`id`)
)');
