<?php

class PerfilController
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

    public function index($id = null)
    {
        if ($id) {
            if (!Validator::positiveInt($id)) {
                Redirect::to('/ranking');
            }
            $usuario = $this->model->getUsuarioConEstadisticas($id);

            if (!$usuario) {
                Redirect::to('/ranking');
            }

            $logueado = Auth::usuario();
            $esPropio = $logueado && $logueado['id'] == $id;

            if ($esPropio) {
                Redirect::to('/perfil');
            }

            $usuario['esEditor'] = Auth::esEditor();
            $usuario['esAdmin'] = Auth::esAdmin();
            $qrUrl = $this->buildQrUrl($usuario['id']);

            $this->renderer->render("perfil", [
                "usuario" => $usuario,
                "esPerfil" => true,
                "esPropio" => false,
                "qrUrl" => $qrUrl,
            ]);
            return;
        }

        $usuario = Auth::requerirLogin();

        $usuario = $this->model->getUsuarioConEstadisticas($usuario['id']);
        $usuario['esEditor'] = Auth::esEditor();
        $usuario['esAdmin'] = Auth::esAdmin();
        $qrUrl = $this->buildQrUrl($usuario['id']);
        
        $this->renderer->render("perfil", [
            "usuario" => $usuario,
            "esPerfil" => true,
            "esPropio" => true,
            "qrUrl" => $qrUrl,
        ]);
    }

    public function editar()
    {
        $usuario = Auth::requerirLogin();

        $data = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $campos = [
                'email' => $this->request->post('email'),
                'nombre' => $this->request->post('nombre'),
                'anio_nacimiento' => $this->request->post('anio_nacimiento'),
                'sexo' => $this->request->post('sexo'),
                'pais' => $this->request->post('pais'),
                'ciudad' => $this->request->post('ciudad'),
            ];

            $cambios = [];
            foreach ($campos as $campo => $valor) {
                if ($valor !== ($usuario[$campo] ?? null)) {
                    $cambios[$campo] = $valor;
                }
            }

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
                        $cambios['foto_perfil'] = 'uploads/perfiles/' . $nombreArchivo;
                    }
                }
            }

            if (!empty($cambios)) {
                $this->model->actualizar($usuario['username'], $cambios);
                $_SESSION['usuario'] = $this->model->getByUsername($usuario['username']);
                $_SESSION['usuario']['esEditor'] = ($_SESSION['usuario']['rol'] ?? 'usuario') === 'editor';
                $_SESSION['usuario']['esAdmin'] = ($_SESSION['usuario']['rol'] ?? 'usuario') === 'admin';
            }

            if (!isset($data['error'])) {
                Redirect::to('/perfil');
            }
        }

        $data['error'] = $data['error'] ?? null;
        $data['usuario'] = $usuario;
        $usuario['sexoMasculino'] = $usuario['sexo'] === 'Masculino';
        $usuario['sexoFemenino'] = $usuario['sexo'] === 'Femenino';
        $usuario['sexoOtro'] = !$usuario['sexoMasculino'] && !$usuario['sexoFemenino'];

        $this->renderer->render("form_editar_perfil", [
            "usuario" => $usuario,
            "esPerfil" => true,
            "error" => $data['error'],
        ]);
    }

    private function buildQrUrl($userId)
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        return $protocol . $_SERVER['HTTP_HOST'] . '/perfil/' . $userId;
    }

}
