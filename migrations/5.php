<?php
    echo "Checking Fokabot...\n";
    $exists = $GLOBALS['db']->fetch("SELECT * FROM users WHERE username = 'FokaBot' AND id = '999'");
    if ($exists) {
        echo 'Fokabot already exists, no action needed.';
    } else {
        echo "Fokabot doesn't exist. Creating account...\n";
        $plainPassword = randomString(8);
        $options = ['cost' => 9, 'salt' => base64_decode(base64_encode(mcrypt_create_iv(22, MCRYPT_DEV_URANDOM)))];
        $md5Password = crypt(md5($plainPassword), '$2y$'.$options['salt']);
        $GLOBALS['db']->execute("INSERT INTO `users` (`id`, `osu_id`, `username`, `password_md5`, `salt`, `email`, `register_datetime`, `rank`, `allowed`, `latest_activity`, `silence_end`, `silence_reason`) VALUES ('999', '999', 'FokaBot', ?, ?, 'fo@kab.ot', '1452544880', '4', '1', '0', '0', '');", [$md5Password, base64_encode($options['salt'])]);
        $GLOBALS['db']->execute("INSERT INTO `users_stats` (`id`, `osu_id`, `username`, `username_aka`, `user_color`, `user_style`, `ranked_score_std`, `playcount_std`, `total_score_std`, `replays_watched_std`, `ranked_score_taiko`, `playcount_taiko`, `total_score_taiko`, `replays_watched_taiko`, `ranked_score_ctb`, `playcount_ctb`, `total_score_ctb`, `replays_watched_ctb`, `ranked_score_mania`, `playcount_mania`, `total_score_mania`, `replays_watched_mania`, `total_hits_std`, `total_hits_taiko`, `total_hits_ctb`, `total_hits_mania`, `country`, `show_country`, `level_std`, `level_taiko`, `level_ctb`, `level_mania`, `avg_accuracy_std`, `avg_accuracy_taiko`, `avg_accuracy_ctb`, `avg_accuracy_mania`, `badges_shown`, `safe_title`, `userpage_content`, `play_style`, `favourite_mode`) VALUES ('999', '999', 'FokaBot', '', 'black', '', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', 'XX', '1', '0', '0', '0', '0', '0.000000000000', '0.000000000000', '0.000000000000', '0.000000000000', '3,4,11,0,0,0', '0', '', '0', '0');");
        echo 'Fokabot account created! Password is: '.$plainPassword."\n";
    }
