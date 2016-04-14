<?php

/**
 *	Functions for theme configuration.
 */
function set_theme_options($options, $value = null)
{
    if (!is_array($options)) {
        $options = [$options => $value];
    }

    // existsing options
    $current = Config::get('theme', []);

    // merge theme config
    Config::set('theme', array_merge($current, $options));
}

function theme_option($option, $default = '')
{
    return Config::get('theme.'.$option, $default);
}
