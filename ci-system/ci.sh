# THE SUPER CONTINUOUS INTEGRATION SYSTEM FOR ripple 1.5

# Go to the right directory.
cd /var/www/ripple.moe/ci-system

# First of all, we need to fetch the repo and merge its contents.
git pull origin production

# Migrations
php migrate.php

# Start main CI stuff
bash ci-stop.sh
nohup bash ci-main.sh 2>&1 >/dev/null

cd ..

# Composer dependencies updater
nohup bash ci-system/ci-composer.sh & 2>&1 >/dev/null

# Update changelog.txt
git log --pretty=format:'%H|%at|%an|%s' > ci-system/changelog.txt
