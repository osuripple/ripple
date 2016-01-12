<?php
/*
 * lastfm.php seems to be called when:
 * - A map is started
 * - Probably more.
 * It theorically should be for sending data to last.fm, but I suspect it can also be used to change status on bancho.
 * 
 * GET parameters:
 * b - the beatmap ID the user is listening/playing
 * action - what the user is doing. I guess this is different from "np" if, for instance, the user is playing the beatmap on the main menu.
 * us - The username of who is listening to that song.
 * ha - The password hash of the username.
 *
 * Response:
 * On test, it says "-3". This might be safe to say to the client at all times. It looks like it means osu! isn't integrated with lastfm with this user.
 */
