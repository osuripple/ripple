<?php
// THE SUPER RIPPLE CONTINUOUS INTEGRATION SYSTEM
// It actually just calls the bash script at the bottom that updates everything locally.
shell_exec("/usr/bin/nohup " . dirname(__FILE__) . "/../ci-system/ci.sh > " . dirname(__FILE__) . "/../nohup.log 2>&1&");
//shell_exec("/usr/bin/nohup " . dirname(__FILE__) . "/../ci-system/ci.sh > " . dirname(__FILE__) . "/../nohup.log 2>&1&");