<?php

class AuthController
{
    private $renderer;

    public function __construct($renderer)
    {
        $this->renderer = $renderer;
    }

    public function verLogin()
    {
        Log::info("AuthController::verLogin");
        $this->renderer->render("formLoginView", ["esLogin" => true]);
    }

    public function verRegistro()
    {
        Log::info("AuthController::verRegistro");
        $this->renderer->render("formRegistrationView", ["esRegistro"=> true]);
    }

    // public function verRegistro()
    // {
    //     Log::info("AuthController::verRegistro");
    //     $this->renderer->render("formRegistroView");
    // }
}
