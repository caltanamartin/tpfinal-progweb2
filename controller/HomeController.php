<?php

class HomeController
{
    private $renderer;

    public function __construct($renderer)
    {
        $this->renderer = $renderer;
    }

    public function ver()
    {
        Log::info("HomeController::ver");
        $this->renderer->render("landing", ["esHome"=> "true"]);
    }
}
