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
        $sql = "INSERT INTO partidas_preguntas (partida_id, pregunta_id, respuesta, es_correcta, orden, respondida_en)
                VALUES (?, ?, ?, ?, ?, NOW())";
        $this->database->executePrepared($sql, [$partidaId, $preguntaId, $respuesta, $esCorrecta, $orden]);
    }

    public function obtenerUltimaRespuesta($partidaId)
    {
        $sql = "SELECT pp.*, p.pregunta, p.respuesta_correcta,
                       p.opcion_a, p.opcion_b, p.opcion_c, p.opcion_d,
                       c.nombre AS categoria_nombre, c.color AS categoria_color
                FROM partidas_preguntas pp
                JOIN preguntas p ON p.id = pp.pregunta_id
                JOIN categorias c ON c.id = p.categoria_id
                WHERE pp.partida_id = ? AND pp.respuesta IS NOT NULL
                ORDER BY pp.orden DESC LIMIT 1";
        $result = $this->database->queryPrepared($sql, [$partidaId]);
        return !empty($result) ? $result[0] : null;
    }

    public function siguienteOrden($partidaId)
    {
        $sql = "SELECT IFNULL(MAX(orden), 0) + 1 AS sig FROM partidas_preguntas WHERE partida_id = ?";
        $result = $this->database->queryPrepared($sql, [$partidaId]);
        return $result[0]['sig'];
    }

}
