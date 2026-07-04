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

    public function getTotalPartidas($filtro = 'mes')
    {
        $intervalo = $this->intervaloSql($filtro);
        $sql = "SELECT COUNT(*) AS total FROM partidas WHERE estado = 'terminada' AND terminada_en >= DATE_SUB(NOW(), INTERVAL $intervalo)";
        $result = $this->database->query($sql);
        return $result[0]['total'];
    }

    public function getPartidasPorPeriodo($filtro = 'mes')
    {
        $intervalo = $this->intervaloSql($filtro);
        $formato = $this->formatoPeriodoSql($filtro, 'terminada_en');
        $sql = "SELECT $formato AS periodo, COUNT(*) AS total
                FROM partidas
                WHERE estado = 'terminada' AND terminada_en >= DATE_SUB(NOW(), INTERVAL $intervalo)
                GROUP BY periodo ORDER BY periodo";
        return $this->database->query($sql);
    }

    private function intervaloSql($filtro)
    {
        switch ($filtro) {
            case 'dia': return '1 DAY';
            case 'semana': return '1 WEEK';
            case 'anio': return '1 YEAR';
            default: return '1 MONTH';
        }
    }

    private function formatoPeriodoSql($filtro, $columna)
    {
        switch ($filtro) {
            case 'dia': return "DATE_FORMAT($columna, '%Y-%m-%d %H:00')";
            case 'anio': return "DATE_FORMAT($columna, '%Y-%m')";
            default: return "DATE($columna)";
        }
    }

    public function getTodas($pagina = null, $porPagina = null)
    {
        $sql = "SELECT p.*, u.username,
                       COUNT(pp.id) AS total_preguntas,
                       IFNULL(SUM(pp.es_correcta), 0) AS respuestas_correctas
                FROM partidas p
                JOIN usuarios u ON u.id = p.usuario_id
                LEFT JOIN partidas_preguntas pp ON pp.partida_id = p.id
                GROUP BY p.id
                ORDER BY p.id DESC";
        if ($pagina && $porPagina) {
            $offset = ($pagina - 1) * $porPagina;
            $countSql = "SELECT COUNT(*) AS total FROM (SELECT p.id FROM partidas p GROUP BY p.id) AS sub";
            $total = $this->database->query($countSql)[0]['total'];
            $sql .= " LIMIT $porPagina OFFSET $offset";
            return ['filas' => $this->database->query($sql), 'total' => $total, 'paginas' => (int)ceil($total / $porPagina)];
        }
        return $this->database->query($sql);
    }

    public function tienePartidas($usuarioId)
    {
        $sql = "SELECT COUNT(*) AS total FROM partidas WHERE usuario_id = $usuarioId AND estado = 'terminada'";
        $result = $this->database->query($sql);
        return $result[0]['total'] > 0;
    }
}
