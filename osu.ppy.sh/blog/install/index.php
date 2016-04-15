<?php
/*
               /   \
              |  o  |
               \   /
        ________) (________
       |                   |
       '------.     .------'
               |   |
               |   |
               |   |
               |   |
    /\         |   |         /\
   /_ \        /   \        / _\
     \ '.    .'     '.    .' /
      \  '--'         '--'  /
       '.                 .'
         '._           _.'
            `'-.   .-'`
                \ /
*/
if (file_exists(dirname(__FILE__) . '/../anchor/config/db.php')) {
    die('lol. try again u scrub');
}
define('DS', DIRECTORY_SEPARATOR);
define('ENV', getenv('APP_ENV'));
define('VERSION', '0.10');
define('PATH', dirname(dirname(__FILE__)) . DS);
define('APP', PATH . 'install' . DS);
define('SYS', PATH . 'system' . DS);
define('EXT', '.php');
require SYS . 'start' . EXT;
