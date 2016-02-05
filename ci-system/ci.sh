# THE SUPER CONTINUOUS INTEGRATION SYSTEM FOR ripple 1.5

# Go to the right directory.
cd /var/www/ripple.moe

# First of all, we need to fetch the repo and merge its contents.
git pull origin production

# Get mysql password
cd ci-system

php migrate.php

# Refresh things a bit by running the cron.
cd ..
cd osu.ppy.sh
php cron.php 2>&1 > /dev/null &

# Update changelog.json
# https://gist.github.com/textarcana/1306223
cd ..
git log --pretty=format:'{%n  "commit": "%H",%n  "author": "%an <%ae>",%n  "date": "%ad",%n  "message": "%f"%n},' $@ | perl -pe 'BEGIN{print "["}; END{print "]\n"}' | perl -pe 's/},]/}]/' > ci-system/changelog.json
