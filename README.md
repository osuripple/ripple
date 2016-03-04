# Ripple

This is the source code that powers [ripple](http://ripple.moe), a private/custom osu! server that has been in development for longer than I'd like to admit. (there's actually a longer history and commit log, but that's part of the shitty code).

## Requirements

Ripple was originally built in PHP, then one day we decided that the code for bancho sucked, so development of the bancho php server and a Python one was built. So now for installing ripple you need:

* PHP
* nginx/Apache
* MySQL
* Python (3)

That's a hell lot of stuff.

## Setting up

I assume that you have PHP set up with nginx, so that nginx forwards requests et cetera. You should forward requests to osu.ppy.sh to `path/to/ripple/osu.ppy.sh`.

Then, the avatar server. That's a simple python application, which only requires flask, so `pip install flask` and then `cd a.ppy.sh && python avatarserver.py`. Reverse proxy to the avatar server, blah blah blah.

Then there's the bancho server, which is in `c.ppy.sh`. Set up dependencies: `pip install flask tornado pymysql psutil`, then start it with `python3 pep.py`.

Config the stuff. For osu.ppy.sh, `osu.ppy.sh/inc/config.sample.php` (copied to config.php in that same dir), and change the stuff there (it's all commented). Then for c.ppy.sh, simply start it and the config file will appear in that folder. Easy as that!

As a final thing, to set up the database, `cd ci-system && php migrate.php` will do all the magic. You can then create a new user with the beta key `betakey`. (then you'd set in the db that they are an admin, and so on).

God I can't believe you're actually considering trying to use this. Are you for real?

## Discord

Want to have some fun? We have a [discord server](https://discord.gg/0rJcZruIsA7nTmtA) where you can smoke some weed with us.
