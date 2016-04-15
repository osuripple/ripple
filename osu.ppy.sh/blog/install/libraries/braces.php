<?php
class braces {
    public static function compile($path, $vars = []) {
        $braces = new static ($path);
        return $braces->render($vars);
    }
    public function __construct($path) {
        $this->path = $path;
    }
    public function render($vars = []) {
        $content = file_get_contents($this->path);
        $keys = array_map([$this, 'key'], array_keys($vars));
        $values = array_values($vars);
        return str_replace($keys, $values, $content);
    }
    public function key($var) {
        return '{{' . $var . '}}';
    }
}
