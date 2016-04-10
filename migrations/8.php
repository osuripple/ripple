<?php
// osu_id is a fucking useless piece of shit.
$GLOBALS["db"]->execute("ALTER TABLE users_stats DROP COLUMN osu_id");
$GLOBALS["db"]->execute("ALTER TABLE users DROP COLUMN osu_id");
$GLOBALS["db"]->execute("ALTER TABLE tokens ADD COLUMN private TINYINT(4);");
