<?php
    // Fix completed = 3 mess
    echo "Fixing top scores. This might take some minutes. Be patient. We're sorry :(\r\n";

    // Get every top score
    $scores = $GLOBALS['db']->fetchAll('SELECT * FROM scores WHERE completed = 3');
    $total = count($scores);

    // For each score, check if there is another top score from the same user on the same map
    $cont = 0;
    $oldPerc = -1;
    $fixed = 0;
    foreach ($scores as $score) {
        $cont++;

        // Find bugged scores
        $buggedScores = $GLOBALS['db']->fetchAll('SELECT * FROM scores WHERE beatmap_md5 = ? AND username = ? AND play_mode = ? AND score < ? AND completed = 3', [$score['beatmap_md5'], $score['username'], $score['play_mode'], $score['score']]);
        if ($buggedScores) {
            // We've found some bugged scores, let's fix them
            foreach ($buggedScores as $buggedScore) {
                // Set bugged score as non-best score
                $GLOBALS['db']->execute('UPDATE scores SET completed = 2 WHERE id = ?', [$buggedScore['id']]);
                $fixed++;
                echo 'Fixed top score '.$buggedScore['id']."\r\n";
            }
        }

        // Calculate percentage and print it only if it changed
        $perc = floor((100 * $cont) / $total);
        if ($perc != $oldPerc) {
            $oldPerc = $perc;
            echo $perc.'% ('.$cont.'/'.$total.")\r\n";
        }
    }

    echo "\r\nDone. Fixed ".$fixed.' bugged scores.';
