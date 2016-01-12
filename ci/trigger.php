<?php
// THE SUPER RIPPLE CONTINUOUS INTEGRATION SYSTEM
// It actually just calls the bash script at the bottom that updates everything locally.
shell_exec("/usr/bin/bash " . dirname(__FILE__) . "/../ci-system/ci.sh /tmp/ci.log 2>>/tmp/ci.log &");