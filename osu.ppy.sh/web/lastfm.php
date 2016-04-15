<?php
/*
 * lastfm.php seems to be called when:
 * - A map is started
 * - Probably more.
 * It theorically should be for sending data to last.fm, but I suspect it can also be used to change status on bancho.
 *
 * GET parameters:
 * b - the beatmap ID the user is listening/playing
 * action - 'np' if the song just started, 'scrobble' if it's been playing for over 40 seconds or if 50% of it passed
 * us - The username of who is listening to that song.
 * ha - The password hash of the username.
 *
 * Response:
 * "-3" if user doesn't have a last.fm account associated with their account
 * anything else if the client does, the client doesn't contain a check for the response
*/
