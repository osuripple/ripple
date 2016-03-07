# THE SUPER CONTINUOUS INTEGRATION SYSTEM FOR ripple 1.5

# Stop avatar server (with basic ctrl+c)
tmux send -t avatarserver C-c

# Trigger bancho ci event (safe shutdown)
curl 127.0.0.1:5001/ci-trigger?k=$(< cikey.txt)

# Wait until bancho has been killed (25 seconds, 5 seconds of margin)
sleep 30

# Go to the right directory.
cd /var/www/ripple.moe

# First of all, we need to fetch the repo and merge its contents.
git pull origin production

# Migrations
cd ci-system
php migrate.php

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

# Update changelog.json
# https://gist.github.com/textarcana/1306223
cd ..
git log --pretty=format:'{%n  "commit": "%H",%n  "author": "%an <%ae>",%n  "date": "%ad",%n  "message": "%f"%n},' $@ | perl -pe 'BEGIN{print "["}; END{print "]\n"}' | perl -pe 's/},]/}]/' > ci-system/changelog.json
