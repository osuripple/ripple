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
 
// Well, if we ain't working in localhost, this might work...
/*require_once dirname(__FILE__) . "/../inc/Curl.php";
$c = new Curl();
$c->setUserAgent('osu!');
$c->setOpt(CURLOPT_ENCODING , 'gzip');
echo $c->get("https://osu.ppy.sh/web/check-updates?action=" . urlencode($_GET["action"]) . "&stream=" . urlencode($_GET["stream"])
           . "&time=" . urlencode($_GET["time"]));*/
?>[{"file_version":"2338","filename":"osu!.exe","file_hash":"fbe80082a80bbb1c8c338db2918e5b21","filesize":"3285560","timestamp":"2015-12-28 15:11:56","patch_id":null,"url_full":"http:\/\/m1.ppy.sh\/r\/osu!.exe\/f_fbe80082a80bbb1c8c338db2918e5b21"}]