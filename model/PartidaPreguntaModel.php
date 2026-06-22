<?php

class PartidaPreguntaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function registrar($partidaId, $preguntaId, $respuesta, $esCorrecta, $orden)
    {
        $respuesta = $respuesta ? "'$respuesta'" : "NULL";
        $esCorrecta = $esCorrecta !== null ? $esCorrecta : "NULL";
        $sql = "INSERT INTO partidas_preguntas (partida_id, pregunta_id, respuesta, es_correcta, orden, respondida_en)
                VALUES ($partidaId, $preguntaId, $respuesta, $esCorrecta, $orden, NOW())";
        $this->database->execute($sql);
    }

    public function obtenerUltimaRespuesta($partidaId)
    {
        $sql = "SELECT pp.*, p.pregunta, p.respuesta_correcta,
                       p.opcion_a, p.opcion_b, p.opcion_c, p.opcion_d,
                       c.nombre AS categoria_nombre, c.color AS categoria_color
                FROM partidas_preguntas pp
                JOIN preguntas p ON p.id = pp.pregunta_id
                JOIN categorias c ON c.id = p.categoria_id
                WHERE pp.partida_id = $partidaId AND pp.respuesta IS NOT NULL
                ORDER BY pp.orden DESC LIMIT 1";
        $result = $this->database->query($sql);
        return $result[0];
    }

    public function obtenerUltimaRespuestaDePartida($partidaId)
    {
        $sql = "SELECT pp.*, p.pregunta, p.respuesta_correcta,
                       p.opcion_a, p.opcion_b, p.opcion_c, p.opcion_d,
                       c.nombre AS categoria_nombre, c.color AS categoria_color
                FROM partidas_preguntas pp
                JOIN preguntas p ON p.id = pp.pregunta_id
                JOIN categorias c ON c.id = p.categoria_id
                WHERE pp.partida_id = $partidaId
                ORDER BY pp.orden DESC LIMIT 1";
        $result = $this->database->query($sql);
        return $result[0];
    }

    public function siguienteOrden($partidaId)
    {
        $sql = "SELECT IFNULL(MAX(orden), 0) + 1 AS sig FROM partidas_preguntas WHERE partida_id = $partidaId";
        $result = $this->database->query($sql);
        return $result[0]['sig'];
    }

}
