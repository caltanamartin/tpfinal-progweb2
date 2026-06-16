<?php

class PartidaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function crear($usuarioId)
    {
        $sql = "INSERT INTO partidas (usuario_id) VALUES ($usuarioId)";
        $this->database->execute($sql);
        return $this->database->lastInsertId();
    }

    public function obtener($partidaId)
    {
        $sql = "SELECT * FROM partidas WHERE id = $partidaId";
        $result = $this->database->query($sql);
        return $result[0];
    }

    public function sumarPunto($partidaId)
    {
        $sql = "UPDATE partidas SET puntaje = puntaje + 1 WHERE id = $partidaId";
        $this->database->execute($sql);
    }

    public function terminar($partidaId)
    {
        $sql = "UPDATE partidas SET estado = 'terminada', terminada_en = NOW() WHERE id = $partidaId";
        $this->database->execute($sql);
    }

    public function historial($usuarioId, $limite = 10)
    {
        $sql = "SELECT * FROM partidas WHERE usuario_id = $usuarioId AND estado = 'terminada'
                ORDER BY creado_en DESC LIMIT $limite";
        return $this->database->query($sql);
    }

    public function puntajeTotal($usuarioId)
    {
        $sql = "SELECT IFNULL(SUM(puntaje), 0) AS total FROM partidas
                WHERE usuario_id = $usuarioId AND estado = 'terminada'";
        $result = $this->database->query($sql);
        return $result[0]['total'];
    }

    public function tienePartidaEnCurso($usuarioId)
    {
        $sql = "SELECT id FROM partidas WHERE usuario_id = $usuarioId AND estado = 'jugando' LIMIT 1";
        $result = $this->database->query($sql);
        return $result[0]['id'];
    }

    public function tienePartidas($usuarioId)
    {
        $sql = "SELECT COUNT(*) AS total FROM partidas WHERE usuario_id = $usuarioId AND estado = 'terminada'";
        $result = $this->database->query($sql);
        return $result[0]['total'] > 0;
    }
}
