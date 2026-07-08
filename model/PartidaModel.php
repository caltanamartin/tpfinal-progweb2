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
        $sql = "INSERT INTO partidas (usuario_id) VALUES (?)";
        $this->database->executePrepared($sql, [$usuarioId]);
        return $this->database->lastInsertIdPrepared();
    }

    public function obtener($partidaId)
    {
        $sql = "SELECT * FROM partidas WHERE id = ?";
        $result = $this->database->queryPrepared($sql, [$partidaId]);
        return $result[0];
    }

    public function sumarPunto($partidaId)
    {
        $sql = "UPDATE partidas SET puntaje = puntaje + 1 WHERE id = ?";
        $this->database->executePrepared($sql, [$partidaId]);
    }

    public function terminar($partidaId)
    {
        $sql = "UPDATE partidas SET estado = 'terminada', terminada_en = NOW() WHERE id = ?";
        $this->database->executePrepared($sql, [$partidaId]);
    }

    public function historial($usuarioId, $limite = 10)
    {
        $sql = "SELECT * FROM partidas WHERE usuario_id = ? AND estado = 'terminada'
                ORDER BY creado_en DESC LIMIT ?";
        return $this->database->queryPrepared($sql, [$usuarioId, $limite]);
    }

    public function puntajeTotal($usuarioId)
    {
        $sql = "SELECT IFNULL(SUM(puntaje), 0) AS total FROM partidas
                WHERE usuario_id = ? AND estado = 'terminada'";
        $result = $this->database->queryPrepared($sql, [$usuarioId]);
        return $result[0]['total'];
    }

    public function tienePartidaEnCurso($usuarioId)
    {
        $sql = "SELECT id FROM partidas WHERE usuario_id = ? AND estado = 'jugando' LIMIT 1";
        $result = $this->database->queryPrepared($sql, [$usuarioId]);
        return !empty($result) ? $result[0]['id'] : null;
    }

    public function getTotalPartidas($filtro = 'mes')
    {
        $intervalo = SqlHelper::intervaloSql($filtro);
        $sql = "SELECT COUNT(*) AS total FROM partidas WHERE estado = 'terminada' AND terminada_en >= DATE_SUB(NOW(), INTERVAL $intervalo)";
        $result = $this->database->queryPrepared($sql);
        return $result[0]['total'];
    }

    public function getPartidasPorPeriodo($filtro = 'mes')
    {
        $intervalo = SqlHelper::intervaloSql($filtro);
        $formato = SqlHelper::formatoPeriodoSql($filtro, 'terminada_en');
        $sql = "SELECT $formato AS periodo, COUNT(*) AS total
                FROM partidas
                WHERE estado = 'terminada' AND terminada_en >= DATE_SUB(NOW(), INTERVAL $intervalo)
                GROUP BY periodo ORDER BY periodo";
        return $this->database->queryPrepared($sql);
    }

    public function getTodas($pagina = null, $porPagina = null)
    {
        if ($pagina && $porPagina) {
            $offset = ($pagina - 1) * $porPagina;
            $sql = "SELECT p.*, u.username,
                           COUNT(pp.id) AS total_preguntas,
                           IFNULL(SUM(pp.es_correcta), 0) AS respuestas_correctas
                    FROM partidas p
                    JOIN usuarios u ON u.id = p.usuario_id
                    LEFT JOIN partidas_preguntas pp ON pp.partida_id = p.id
                    GROUP BY p.id
                    ORDER BY p.id DESC LIMIT ? OFFSET ?";
            $countSql = "SELECT COUNT(*) AS total FROM (SELECT p.id FROM partidas p GROUP BY p.id) AS sub";
            $total = $this->database->queryPrepared($countSql)[0]['total'];
            return ['filas' => $this->database->queryPrepared($sql, [$porPagina, $offset]), 'total' => $total, 'paginas' => (int)ceil($total / $porPagina)];
        }
        $sql = "SELECT p.*, u.username,
                       COUNT(pp.id) AS total_preguntas,
                       IFNULL(SUM(pp.es_correcta), 0) AS respuestas_correctas
                FROM partidas p
                JOIN usuarios u ON u.id = p.usuario_id
                LEFT JOIN partidas_preguntas pp ON pp.partida_id = p.id
                GROUP BY p.id
                ORDER BY p.id DESC";
        return $this->database->queryPrepared($sql);
    }

    public function tienePartidas($usuarioId)
    {
        $sql = "SELECT COUNT(*) AS total FROM partidas WHERE usuario_id = ? AND estado = 'terminada'";
        $result = $this->database->queryPrepared($sql, [$usuarioId]);
        return $result[0]['total'] > 0;
    }
}
