<?php

class UsuarioModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getByEmail($email)
    {
        $sql = "SELECT * FROM usuarios WHERE email = '$email'";
        $result = $this->database->query($sql);
        return !empty($result) ? $result[0] : null;
    }

    public function crear($email, $nombre, $password)
    {
        $sql = "INSERT INTO usuarios (email, nombre, password) VALUES ('$email', '$nombre', '$password')";
        return $this->database->execute($sql);
    }
}
