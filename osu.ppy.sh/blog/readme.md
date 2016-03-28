## Anchor CMS

Anchor is a super-simple, lightweight blog system, made to let you just write. [Check out the site](http://anchorcms.com/).

[![Feature Requests](http://feathub.com/anchorcms/anchor-cms?format=svg)](http://feathub.com/anchorcms/anchor-cms)

### Requirements

- PHP 5.3.6+
    - curl
    - mcrypt
    - gd
    - pdo\_mysql or pdo\_sqlite
- MySQL 5.2+

To determine your PHP version, create a new file with this PHP code: `<?php echo PHP_VERSION; // version.php`. This will print your version number to the screen.

### Installation

1. Ensure that you have the required components.
2. Run `composer install` to get dependencies.
3. Navigate to /blog on your ripple instance.
4. Go through the 40-second install procedure.
5. Set up nginx like said on the [wiki](https://github.com/osuripple/ripple/wiki/Blog-set-up)
6. You don't need to remove the `install`, although if you don't plan to stay cutting-edge with the ripple updates you can do it. Note that the install page does not allow to be accessed if there already is a db configuration file.
