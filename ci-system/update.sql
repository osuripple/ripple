/* Update query goes here */
-- Adminer 4.2.3 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `bancho_settings`;
CREATE TABLE `bancho_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `value_int` int(11) NOT NULL DEFAULT '0',
  `value_string` varchar(512) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `bancho_settings` (`id`, `name`, `value_int`, `value_string`) VALUES
(1,	'bancho_maintenance',	0,	''),
(2,	'free_direct',	0,	''),
(3,	'menu_icon',	0,	'http://y.zxq.co/mpyxts.png|http://ripple.moe'),
(4,	'login_messages',	0,	'FokaBot|Welcome to Ripple! Bancho is still work in progress and chat doesn\'t work yet. Have fun!\r\nFokaBot|Visit http://ripple.moe/ for more information.'),
(5,	'restricted_joke',	1,	'');

-- 2016-01-13 19:52:18