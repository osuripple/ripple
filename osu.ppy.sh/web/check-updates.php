<?php
/*
 * Checks for updates of the osu! client. Simple.
 *
 * GET parameters:
 * action - "check" seems to be default.
 * stream - The release stream being used e.g. stable
 * time - ???? it should be the time, but it does not look like an UNIX timestamp...
 *
 * Response: A JSON list filled with objects like this:
 * "file_version":"3","filename":"avcodec-51.dll","file_hash":"b22bf1e4ecd4be3d909dc68ccab74eec","filesize":"4409856","timestamp":"2014-08-18 16:16:59","patch_id":"1349","url_full":"http:\/\/m1.ppy.sh\/r\/avcodec-51.dll\/f_b22bf1e4ecd4be3d909dc68ccab74eec","url_patch":"http:\/\/m1.ppy.sh\/r\/avcodec-51.dll\/p_b22bf1e4ecd4be3d909dc68ccab74eec_734e450dd85c16d62c1844f10c6203c0"}
 *
 * Idea that came up to my mind.
 * Stable channel has the latest working osu! beta, which is downloaded on the server. Beta and cuttingedge have the osu! versions proxied from the server
 * although they may not work.
*/
if (gethostbyname('osu.ppy.sh') == '127.0.0.1') {
    echo '[{"file_version":"3","filename":"avcodec-51.dll","file_hash":"b22bf1e4ecd4be3d909dc68ccab74eec","filesize":"4409856","timestamp":"2014-08-18 16:16:59","patch_id":"1349","url_full":"http:\/\/m1.ppy.sh\/r\/avcodec-51.dll\/f_b22bf1e4ecd4be3d909dc68ccab74eec","url_patch":"http:\/\/m1.ppy.sh\/r\/avcodec-51.dll\/p_b22bf1e4ecd4be3d909dc68ccab74eec_734e450dd85c16d62c1844f10c6203c0"}]';
    exit;
}
$resget = file_get_contents('https://osu.ppy.sh/web/check-updates.php?action=' . urlencode($_GET['action']) . '&stream=' . urlencode($_GET['stream']) . '&time=' . urlencode($_GET['time']));
echo $resget;
