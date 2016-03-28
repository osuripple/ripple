#!/usr/bin/bash

# Wait until bancho has been killed (25 seconds, 5 seconds of margin)
sleep 30

# Start bancho
tmux send -t bancho 'cd /var/www/ripple.moe/c.ppy.sh && python3.5 pep.py'
tmux send -t bancho 'ENTER'

# Start avatar server
tmux send -t avatarserver 'cd /var/www/ripple.moe/a.ppy.sh && python3.5 avatarserver.py'
tmux send -t avatarserver 'ENTER'

# Refresh things a bit by running the cron.
cd ..
cd osu.ppy.sh
php cron.php 2>&1 > /dev/null &
cd ..