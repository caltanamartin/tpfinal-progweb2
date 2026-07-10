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

    public function login()
    {
        $data = ["esLogin" => true];

        if (isset($_SESSION['exito'])) {
            $data['exito'] = $_SESSION['exito'];
            unset($_SESSION['exito']);
        }

        if (isset($_SESSION['verificacion_exito'])) {
            $data['exito'] = $_SESSION['verificacion_exito'];
            unset($_SESSION['verificacion_exito']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $this->request->post('username');
            $password = $this->request->post('password');

            $usuario = $this->model->getByUsername($username);

            if ($usuario && password_verify($password, $usuario['password'])) {
                if (!$usuario['verificado']) {
                    $data['error'] = "Tenés que verificar tu cuenta por email antes de iniciar sesión.";
                } else {
                    session_regenerate_id(true);
                    $_SESSION['usuario'] = $usuario;
                    $_SESSION['usuario']['esEditor'] = ($usuario['rol'] === 'editor');
                    $_SESSION['usuario']['esAdmin'] = ($usuario['rol'] === 'admin');
                    Redirect::toIndex();
                }
            } else {
                $data['error'] = "Usuario o contraseña incorrectos.";
            }
        }

        $this->renderer->render("form_login", $data);
    }

    public function registro()
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
                    $ext = strtolower(pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION));
                    $extPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (!in_array($ext, $extPermitidas)) {
                        $data['error'] = "Formato de imagen no válido. Permitidos: " . implode(', ', $extPermitidas);
                    } elseif ($_FILES['foto_perfil']['size'] > 2 * 1024 * 1024) {
                        $data['error'] = "La imagen supera el tamaño máximo de 2MB.";
                    } else {
                        $nombreArchivo = uniqid('perfil_') . '.' . $ext;
                        $ruta = __DIR__ . '/../uploads/perfiles/' . $nombreArchivo;
                        if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $ruta)) {
                            $fotoPerfil = 'uploads/perfiles/' . $nombreArchivo;
                        }
                    }
                }

                if (!isset($data['error'])) {
                    $this->model->crear($email, $nombre, $username, $password, $anioNacimiento, $sexo, $pais, $ciudad, $fotoPerfil);
                    $usuario = $this->model->getByUsername($username);
                    $token = bin2hex(random_bytes(32));
                    $this->model->saveToken($usuario['id'], $token);

                    $baseUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
                    $link = $baseUrl . '/verificar/' . $token;

                    $subject = "Verificá tu cuenta en PreguntaTres";
                    $body = "<h1>Bienvenido a PreguntaTres</h1>
                             <p>Hacé clic en el siguiente enlace para verificar tu cuenta:</p>
                             <p><a href=\"$link\">$link</a></p>
                             <p>Si no te registraste, ignorá este mensaje.</p>";

                    Mailer::send($email, $subject, $body);

                    $_SESSION['exito'] = "Registrado correctamente. Revisá tu email para verificar la cuenta.";
                    Redirect::to('/auth/login');
                }
            }
        }

        $this->renderer->render("form_registro", $data);
    }

    public function verificar()
    {
        $token = $this->request->get('token');
        if ($token) {
            Redirect::to('/verificar/' . $token);
        }
        Redirect::toIndex();
    }

    public function logout()
    {
        session_destroy();
        Redirect::toIndex();
    }
}
