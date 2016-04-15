<?php
class layout {
    public static function create($path, $vars = []) {
        return View::create($path, $vars)->partial('header', 'partials/header', $vars)->partial('footer', 'partials/footer', $vars);
    }
}
