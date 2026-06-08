<?php

class AuthController
{
    private $renderer;
    private $model;
    private $request;

    public function __construct($renderer, $model, $request)
    {
        $this->renderer = $renderer;
        $this->model = $model;
        $this->request = $request;
    }

    public function verLogin()
    {
        $data = ["esLogin" => true];

        if (isset($_SESSION['exito'])) {
            $data['exito'] = $_SESSION['exito'];
            unset($_SESSION['exito']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $this->request->post('email');
            $password = $this->request->post('password');

            $usuario = $this->model->getByEmail($email);

            if ($usuario && $password === $usuario['password']) {
                $_SESSION['usuario'] = $usuario;
                Redirect::to('/');
            }

            $data['error'] = "Email o contraseña incorrectos.";
        }

        $this->renderer->render("formLoginView", $data);
    }

    public function verRegistro()
    {
        $data = ["esRegistro" => true];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $this->request->post('email');
            $nombre = $this->request->post('nombre');
            $password = $this->request->post('password');
            $passwordConfirm = $this->request->post('password_confirm');

            if ($password !== $passwordConfirm) {
                $data['error'] = "Las contraseñas no coinciden.";
            } elseif ($this->model->getByEmail($email)) {
                $data['error'] = "Ya existe un usuario con ese email.";
            } else {
                $this->model->crear($email, $nombre, $password);
                $_SESSION['exito'] = "Usuario registrado correctamente. Iniciá sesión.";
                Redirect::to('/login');
            }
        }

        $this->renderer->render("formRegistrationView", $data);
    }

    public function logout()
    {
        session_destroy();
        Redirect::to('/');
    }
}
