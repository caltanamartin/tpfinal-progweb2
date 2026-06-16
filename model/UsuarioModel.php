<?php

class UsuarioModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getByUsername($username)
    {
        $sql = "SELECT * FROM usuarios WHERE username = '$username'";
        $result = $this->database->query($sql);
        return !empty($result) ? $result[0] : null;
    }

    public function getUsuarioConEstadisticas($id)
    {
        $sql = "SELECT u.*,
                   COALESCE(SUM(p.puntaje), 0) AS puntaje_total,
                   COUNT(p.id) AS cantidad_partidas
            FROM usuarios u
            LEFT JOIN partidas p ON p.usuario_id = u.id AND p.estado = 'terminada'
            WHERE u.id = $id
            GROUP BY u.id";
        $result = $this->database->query($sql);
        return $result[0] ?? null;
    }

    public function getByEmail($email)
    {
        $sql = "SELECT * FROM usuarios WHERE email = '$email'";
        $result = $this->database->query($sql);
        return !empty($result) ? $result[0] : null;
    }

    public function crear($email, $nombre, $username, $password, $anioNacimiento, $sexo, $pais, $ciudad, $fotoPerfil)
    {
        $sql = "INSERT INTO usuarios (email, nombre, username, password, anio_nacimiento, sexo, pais, ciudad, foto_perfil)
                VALUES ('$email', '$nombre', '$username', '$password', '$anioNacimiento', '$sexo', '$pais', '$ciudad', '$fotoPerfil')";
        return $this->database->execute($sql);
    }

    public function actualizar($username, $cambios)
    {
        if (empty($cambios)) {
            return 0;
        }

        $sets = [];
        foreach ($cambios as $campo => $valor) {
            $sets[] = "$campo = '$valor'";
        }

        $sql = "UPDATE usuarios SET " . implode(', ', $sets) . " WHERE username = '$username'";
        return $this->database->execute($sql);
    }
}
