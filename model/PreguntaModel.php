<?php

class PreguntaModel
{
    private $database;
    private $minRespuestasGlobales = 10;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getPreguntaAleatoria($partidaId, $usuarioId)
    {
        $nivelUsuario = $this->calcularNivelUsuario($usuarioId);
        $distribucion = $this->obtenerDistribucion($nivelUsuario);

        $rand = rand(1, 100);
        $dificultadElegida = 'neutra';
        foreach ($distribucion as $limite => $dificultad) {
            if ($rand <= $limite) {
                $dificultadElegida = $dificultad;
                break;
            }
        }

        $idsEnPartida = $this->obtenerIdsVistosEnPartida($partidaId);
        $filtroExcluir = !empty($idsEnPartida) ? "p.id NOT IN (" . implode(',', $idsEnPartida) . ")" : "1=1";

        $pregunta = $this->buscarPorDificultad($dificultadElegida, $filtroExcluir);
        if ($pregunta) return $pregunta;

        $pregunta = $this->buscarPorDificultad('neutra', $filtroExcluir);
        if ($pregunta) return $pregunta;

        return $this->buscarTodas($filtroExcluir);
    }

    private function buscarPorDificultad($dificultad, $filtroExclusion)
    {
        $order = ($dificultad === 'neutra')
            ? "total_respuestas_global DESC, RAND()"
            : "RAND()";

        if ($dificultad === 'facil') {
            $having = "total_respuestas_global >= $this->minRespuestasGlobales AND (total_correctas_global / total_respuestas_global) > 0.7";
        } elseif ($dificultad === 'dificil') {
            $having = "total_respuestas_global >= $this->minRespuestasGlobales AND (total_correctas_global / total_respuestas_global) < 0.3";
        } elseif ($dificultad === 'media') {
            $having = "total_respuestas_global >= $this->minRespuestasGlobales AND (total_correctas_global / total_respuestas_global) BETWEEN 0.3 AND 0.7";
        } else {
            $having = "(total_respuestas_global < $this->minRespuestasGlobales OR total_respuestas_global IS NULL)";
        }

        $sql = "SELECT p.*, c.nombre AS categoria_nombre, c.color AS categoria_color,
                       COUNT(pp.id) AS total_respuestas_global,
                       IFNULL(SUM(pp.es_correcta), 0) AS total_correctas_global
                FROM preguntas p
                JOIN categorias c ON c.id = p.categoria_id
                LEFT JOIN partidas_preguntas pp ON pp.pregunta_id = p.id
                WHERE p.activa = 1 AND $filtroExclusion
                GROUP BY p.id
                HAVING $having
                ORDER BY $order
                LIMIT 1";

        $result = $this->database->query($sql);
        return !empty($result) ? $result[0] : null;
    }

    private function buscarTodas($filtroExclusion)
    {
        $sql = "SELECT p.*, c.nombre AS categoria_nombre, c.color AS categoria_color,
                       COUNT(pp.id) AS total_respuestas_global,
                       IFNULL(SUM(pp.es_correcta), 0) AS total_correctas_global
                FROM preguntas p
                JOIN categorias c ON c.id = p.categoria_id
                LEFT JOIN partidas_preguntas pp ON pp.pregunta_id = p.id
                WHERE p.activa = 1 AND $filtroExclusion
                GROUP BY p.id
                ORDER BY RAND() LIMIT 1";

        $result = $this->database->query($sql);
        return !empty($result) ? $result[0] : null;
    }

    private function obtenerDistribucion($nivelUsuario)
    {
        if ($nivelUsuario > 0.7) {
            return [50 => 'dificil', 75 => 'media', 80 => 'facil', 100 => 'neutra'];
        } elseif ($nivelUsuario < 0.3) {
            return [5 => 'dificil', 30 => 'media', 80 => 'facil', 100 => 'neutra'];
        } else {
            return [15 => 'dificil', 65 => 'media', 80 => 'facil', 100 => 'neutra'];
        }
    }

    public function calcularNivelUsuario($usuarioId)
    {
        $sql = "SELECT COUNT(*) AS total,
                       IFNULL(SUM(es_correcta), 0) AS correctas
                FROM partidas_preguntas pp
                JOIN partidas p ON p.id = pp.partida_id
                WHERE p.usuario_id = $usuarioId AND pp.respuesta IS NOT NULL";
        $result = $this->database->query($sql);
        $total = (int)$result[0]['total'];

        if ($total < 5) {
            return 0.5;
        }

        return (int)$result[0]['correctas'] / $total;
    }

    private function obtenerIdsVistosEnPartida($partidaId)
    {
        $result = $this->database->query(
            "SELECT pregunta_id FROM partidas_preguntas WHERE partida_id = $partidaId"
        );
        return array_column($result, 'pregunta_id');
    }

    public function getPreguntaConCategoria($preguntaId)
    {
        $sql = "SELECT p.*, c.nombre AS categoria_nombre, c.color AS categoria_color
                FROM preguntas p
                JOIN categorias c ON c.id = p.categoria_id
                WHERE p.id = $preguntaId";
        $result = $this->database->query($sql);
        return !empty($result) ? $result[0] : null;
    }

    public function getCategorias()
    {
        return $this->database->query("SELECT * FROM categorias");
    }
}
