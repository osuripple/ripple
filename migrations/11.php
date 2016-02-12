<?php
$GLOBALS["db"]->execute("ALTER TABLE users_stats ADD favourite_mode TINYINT NOT NULL");
