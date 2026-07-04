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

    public function ver()
    {
        $usuario = $_SESSION['usuario'] ?? null;
        
        if (!$usuario) {
            Redirect::to('/login');
        }

        $usuario = $this->model->getUsuarioConEstadisticas($usuario['id']);
        $usuario['esEditor'] = ($_SESSION['usuario']['rol'] ?? 'usuario') === 'editor';
        $usuario['esAdmin'] = ($_SESSION['usuario']['rol'] ?? 'usuario') === 'admin';
        $qrUrl = $this->buildQrUrl($usuario['id']);
        
        $this->renderer->render("perfil", [
            "usuario" => $usuario,
            "esPerfil" => true,
            "esPropio" => true,
            "qrUrl" => $qrUrl,
        ]);
    }

    public function editarPerfil()
    {
        $usuario = $_SESSION['usuario'] ?? null;

        if (!$usuario) {
            Redirect::to('/login');
        }

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
                $ext = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
                $nombreArchivo = uniqid('perfil_') . '.' . $ext;
                $ruta = __DIR__ . '/../uploads/perfiles/' . $nombreArchivo;
                if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $ruta)) {
                    $cambios['foto_perfil'] = 'uploads/perfiles/' . $nombreArchivo;
                }
            }

            if (!empty($cambios)) {
                $this->model->actualizar($usuario['username'], $cambios);
                $_SESSION['usuario'] = $this->model->getByUsername($usuario['username']);
                $_SESSION['usuario']['esEditor'] = ($_SESSION['usuario']['rol'] ?? 'usuario') === 'editor';
                $_SESSION['usuario']['esAdmin'] = ($_SESSION['usuario']['rol'] ?? 'usuario') === 'admin';
            }

            Redirect::to('/perfil');
        }

        $usuario['sexoMasculino'] = $usuario['sexo'] === 'Masculino';
        $usuario['sexoFemenino'] = $usuario['sexo'] === 'Femenino';
        $usuario['sexoOtro'] = !$usuario['sexoMasculino'] && !$usuario['sexoFemenino'];

        $this->renderer->render("formEditarPerfilView", [
            "usuario" => $usuario,
            "esPerfil" => true,
        ]);
    }

    private function buildQrUrl($userId)
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        return $protocol . $_SERVER['HTTP_HOST'] . '/perfil/' . $userId;
    }

    public function verPublico()
    {
        $id = $this->request->get('id');
        $usuario = $this->model->getUsuarioConEstadisticas($id);

        if (!$usuario) {
            Redirect::to('/ranking');
        }

        $esPropio = isset($_SESSION['usuario']) && $_SESSION['usuario']['id'] == $id;

        if ($esPropio) {
            Redirect::to('/perfil');
        }

        $usuario['esEditor'] = ($_SESSION['usuario']['rol'] ?? 'usuario') === 'editor';
        $usuario['esAdmin'] = ($_SESSION['usuario']['rol'] ?? 'usuario') === 'admin';
        $qrUrl = $this->buildQrUrl($usuario['id']);

        $this->renderer->render("perfil", [
            "usuario" => $usuario,
            "esPerfil" => true,
            "esPropio" => $esPropio,
            "qrUrl" => $qrUrl,
        ]);
    }
}
