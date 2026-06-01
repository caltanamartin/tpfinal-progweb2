<?php

require_once(__DIR__ . '/../vendor/mustache/src/Mustache/Autoloader.php');

class MustacheRenderer {
    private $mustache;

    public function __construct($viewsFolder) {
        Mustache_Autoloader::register();
        $this->mustache = new Mustache_Engine([
            'loader'          => new Mustache_Loader_FilesystemLoader($viewsFolder),
            'partials_loader'  => new Mustache_Loader_FilesystemLoader($viewsFolder),
        ]);
    }

    public function render($viewName, $data = []) {
        $template = $this->mustache->loadTemplate($viewName);
        echo $template->render($data);
    }
}