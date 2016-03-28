# THE SUPER CONTINUOUS INTEGRATION SYSTEM FOR ripple 1.5

# Go to the right directory.
cd /var/www/ripple.moe/ci-system

# Stop avatar server (with basic ctrl+c)
tmux send -t avatarserver C-c

# Trigger bancho ci event (safe shutdown)
cikey=`cat cikey.txt`
curl 127.0.0.1:5001/ci-trigger?k=$cikey

# Wait until bancho has been killed (25 seconds, 5 seconds of margin)
sleep 30

# First of all, we need to fetch the repo and merge its contents.
git pull origin production

# Migrations
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

# Run composer in various directories.
originaldir=$(pwd)
for i in 'osu.ppy.sh/blog'; do
	cd $i
	composer install
	cd $originaldir
done

# Update changelog.json
# https://gist.github.com/textarcana/1306223
cd ..
git log --pretty=format:'%H|%at|%an|%s' > ci-system/changelog.txt
