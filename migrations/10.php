<?php
$GLOBALS["db"]->execute("ALTER TABLE users DROP COLUMN password_secure;");
