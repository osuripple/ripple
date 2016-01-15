# THE SUPER CONTINUOUS INTEGRATION SYSTEM FOR ripple 1.5

# Go to the right directory.
cd /var/www/ripple.moe

# First of all, we need to fetch the repo and merge its contents.
git pull origin production

cd ci-system
if ! cmp update.sql update.sql~ >/dev/null 2>&1
then
  export MYSQL_PWD=$(cat mysqlpassword.txt)
  mysql -u ripple -D ripple < update.sql
fi

if ! cmp pre-update.php pre-update.php~ >/dev/null 2>&1
then
  php pre-update.php
fi

# Refresh things a bit by running the cron.
cd ..
cd osu.ppy.sh
php cron.php 2>&1 > /dev/null &

# Update changelog.json
# https://gist.github.com/textarcana/1306223
cd ..
git log --pretty=format:'{%n  "commit": "%H",%n  "author": "%an <%ae>",%n  "date": "%ad",%n  "message": "%f"%n},' $@ | perl -pe 'BEGIN{print "["}; END{print "]\n"}' | perl -pe 's/},]/}]/' > ci-system/changelog.json


# Trigger the post-update script
cd ..
cd ci-system
if ! cmp post-update.php post-update.php~ >/dev/null 2>&1
then
  php post-update.php
fi

# Last thing: copy update.sql to update.sql~ for the future.
# Same for pre/post-update.php
cp update.sql update.sql~
cp pre-update.php pre-update.php~
cp post-update.php post-update.php~
