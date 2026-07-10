<?php

class Auth
{
    public static function usuario()
    {
        return $_SESSION['usuario'] ?? null;
    }

    public static function requerirLogin()
    {
        $usuario = self::usuario();
        if (!$usuario) {
            Redirect::to('/auth/login');
        }
        return $usuario;
    }

    public static function requerirAdmin()
    {
        $usuario = self::requerirLogin();
        if ($usuario['rol'] !== 'admin') {
            Redirect::toIndex();
        }
        return $usuario;
    }

    public static function requerirEditor()
    {
        $usuario = self::requerirLogin();
        if ($usuario['rol'] !== 'editor') {
            Redirect::toIndex();
        }
        return $usuario;
    }

    public static function requerirEditorOAdmin()
    {
        $usuario = self::requerirLogin();
        if (!in_array($usuario['rol'], ['editor', 'admin'])) {
            Redirect::toIndex();
        }
        return $usuario;
    }

    public static function esAdmin()
    {
        return (self::usuario()['rol'] ?? null) === 'admin';
    }

    public static function esEditor()
    {
        return (self::usuario()['rol'] ?? null) === 'editor';
    }
}
