#!/usr/bin/env bash
cd /var/www/ripple.moe/ci-system
nohup bash 'actual-ci-this-time-i-swear.sh' & 2>&1 >/dev/null
