<?php
/*
 * See http://blog.ppy.sh/post/125425126423/20150730 .
 * Called before anything else. Literally the first API call the sniffer perceived.
 *
 * GET parameters:
 * u - The username
 * h - The password hash
 * info - A json containing the information specified in the post mentioned above.
 *
 * This call seems to not reply with anything. Therefore this file will do it, as we don't return anything back to the user.
*/
