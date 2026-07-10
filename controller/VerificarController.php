<?php

class VerificarController
{
    private $model;

    public function __construct($model, $renderer)
    {
        $this->model    = $model;
        $this->renderer = $renderer;
    }

    public function verificar($token)
    {
        $usuario = $this->model->findByToken($token);

        if (!$usuario) {
            $data['error'] = "El enlace de verificación no es válido o ya expiró.";
            $this->renderer->render("form_login", $data);
            return;
        }

        $this->model->setVerificado($usuario['id']);
        $_SESSION['verificacion_exito'] = "Cuenta verificada correctamente. Ya podés iniciar sesión.";
        Redirect::to('/auth/login');
    }
}
