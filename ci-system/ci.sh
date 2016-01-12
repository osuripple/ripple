# THE SUPER CONTINUOUS INTEGRATION SYSTEM FOR ripple 1.5

# Go to the right directory.
cd /var/www/ripple.moe

# We're working on the production branch, as this is a production server.
git checkout production

# First of all, we need to fetch the repo and merge its contents.
git pull --rebase origin production

if ! cmp update.sql update.sql~ >/dev/null 2>&1
then
  mysql -u ripple "-p$(cat mysqlpassword.txt)" -D ripple < update.sql
fi

if ! cmp pre-update.php pre-update.php~ >/dev/null 2>&1
then
  php pre-update.php
fi

# Refresh things a bit by running the cron.
cd ..
cd osu.ppy.sh
php cron.php 2>&1 > /dev/null &

# Trigger the post-update script
if ! cmp post-update.php post-update.php~ >/dev/null 2>&1
then
  php post-update.php
fi

# Last thing: copy update.sql to update.sql~ for the future.
# Same for pre/post-update.php
cd ..
cp update.sql update.sql~
cp pre-update.php pre-update.php~
cp post-update.php post-update.php~