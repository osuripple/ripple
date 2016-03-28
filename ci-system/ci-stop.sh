#!/usr/bin/env bash
# Stop avatar server (with basic ctrl+c)
tmux send -t avatarserver C-c

# Trigger bancho ci event (safe shutdown)
cikey=`cat cikey.txt`
curl 127.0.0.1:5001/ci-trigger?k=$cikey