<?php

// THE SUPER RIPPLE CONTINUOUS INTEGRATION SYSTEM
// It actually just calls the bash script at the bottom that updates everything locally.
$data = json_decode(file_get_contents('php://input'), true);
if ($data['ref'] == 'refs/heads/production') {
    echo 'doing';
    shell_exec('/usr/bin/env bash ' . dirname(__FILE__) . '/../ci-system/run-ci.sh &');
} else {
    echo 'ignored';
}
