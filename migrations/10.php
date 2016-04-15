<?php

$GLOBALS['db']->execute('ALTER TABLE tokens MODIFY private TINYINT(4) NOT NULL;');
