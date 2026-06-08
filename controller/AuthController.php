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
            $username = $this->request->post('username');
            $password = $this->request->post('password');

            $usuario = $this->model->getByUsername($username);

            if ($usuario && $password === $usuario['password']) {
                $_SESSION['usuario'] = $usuario;
                Redirect::to('/');
            }

            $data['error'] = "Usuario o contraseña incorrectos.";
        }

        $this->renderer->render("formLoginView", $data);
    }

    public function verRegistro()
    {
        $data = ["esRegistro" => true];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $this->request->post('email');
            $nombre = $this->request->post('nombre');
            $username = $this->request->post('username');
            $password = $this->request->post('password');
            $passwordConfirm = $this->request->post('password_confirm');
            $anioNacimiento = $this->request->post('anio_nacimiento');
            $sexo = $this->request->post('sexo');
            $pais = $this->request->post('pais');
            $ciudad = $this->request->post('ciudad');

            if ($password !== $passwordConfirm) {
                $data['error'] = "Las contraseñas no coinciden.";
            } elseif ($this->model->getByUsername($username)) {
                $data['error'] = "El nombre de usuario ya está en uso.";
            } elseif ($this->model->getByEmail($email)) {
                $data['error'] = "Ya existe un usuario con ese email.";
            } else {
                $fotoPerfil = '';
                if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
                    $nombreArchivo = uniqid('perfil_') . '.' . $ext;
                    $ruta = __DIR__ . '/../uploads/perfiles/' . $nombreArchivo;
                    if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $ruta)) {
                        $fotoPerfil = 'uploads/perfiles/' . $nombreArchivo;
                    }
                }

                $this->model->crear($email, $nombre, $username, $password, $anioNacimiento, $sexo, $pais, $ciudad, $fotoPerfil);
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
