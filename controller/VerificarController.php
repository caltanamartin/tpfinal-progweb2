<?php

class VerificarController
{
    private $model;
    private $renderer;
    private $request;

    public function __construct($model, $renderer, $request)
    {
        $this->model    = $model;
        $this->renderer = $renderer;
        $this->request  = $request;
    }

    public function verificar()
    {
        $token = $this->request->get('token');
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
