<?php
    // Fix full combo
    try {
        require_once dirname(__FILE__).'/../osu.ppy.sh/inc/functions.php';

        echo "Fixing FC scores...\r\n";

        $API_KEY = 'API_KEY_HERE';
        $count = $GLOBALS['db']->fetch('SELECT COUNT(*) FROM scores WHERE misses_count = 0');
        if (current($count) == 0) {
            echo "There aren't 0 misses score on the server. No action needed.";
            throw new Exception();
        }

        if ($API_KEY == 'API_KEY_HERE') {
            echo "\033[31m!!!! IMPORTANT !!!!\r\nCouldn't fix FCs scores\r\nPlease open /migrations/2.php and set $API_KEY to your osu! api key\r\nThen enter in migrations folder and run 'php 2.php'.\033[0m";
            throw new Exception();
        }

        // :(
        echo "Fixing FCs. This might take some minutes. Be patient. We're sorry. Again. :(\r\n";

        // Cache
        $maxComboCache = [];

        // Get every score
        $scores = $GLOBALS['db']->fetchAll('SELECT id, beatmap_md5, max_combo, full_combo FROM scores WHERE full_combo = 0 AND misses_count = 0');
        $total = count($scores);

        $cont = 0;
        $fixed = 0;
        foreach ($scores as $score) {
            $cont++;

            // Cache max_combo from osu api if not already in cache
            if (!array_key_exists($score['beatmap_md5'], $maxComboCache)) {
                // Get max combo for that map from osu api and add it to cache
                $url = sprintf('http://osu.ppy.sh/api/get_beatmaps?k=%s&h=%s', $API_KEY, $score['beatmap_md5']);
                $apiData = json_decode(file_get_contents($url), true);
                if (isset($apiData) && !empty($apiData)) {
                    if (array_key_exists('max_combo', $apiData[0])) {
                        $maxComboCache[$score['beatmap_md5']] = $apiData[0]['max_combo'];
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }
            }

            // Check if this score is full combo
            if ($score['max_combo'] == $maxComboCache[$score['beatmap_md5']]) {
                // FC, update table row
                $GLOBALS['db']->execute('UPDATE scores SET full_combo = 1 WHERE id = ?', [$score['id']]);
                $fixed++;
                echo "\033[32mFixed FC score ".$score['id']."\033[0m\r\n";
            }

            // Wait some time so we don't abuse osu api
            sleep(0.2);

            // Calculate percentage and print it only if it changed
            $perc = number_format((100 * $cont) / $total, 2);
            echo $perc.'% ('.$cont.'/'.$total.")\r\n";
        }

        echo "\r\nDone. Fixed ".$fixed.' FC scores.';
    } catch (Exception $e) {
        if ($e->getMessage() != '') {
            echo "\n\nERROR WHILE FIXING FCs. Stopped on index: ".$cont."\nPlease run /migrate/2.php again.\n\n";
        }
    }
