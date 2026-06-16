<?php

class PreguntaModel
{
    private $database;
    private $minRespuestasParaDificultad = 5;

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

        $idsVistos = $this->obtenerIdsVistosPorUsuario($usuarioId);
        $idsEnPartida = $this->obtenerIdsVistosEnPartida($partidaId);
        $todosExcluir = array_unique(array_merge($idsVistos, $idsEnPartida));
        $filtroExcluir = !empty($todosExcluir) ? "p.id NOT IN (" . implode(',', $todosExcluir) . ")" : "1=1";
        $filtroSoloPartida = !empty($idsEnPartida) ? "p.id NOT IN (" . implode(',', $idsEnPartida) . ")" : "1=1";

        $pregunta = $this->buscarPorDificultad($dificultadElegida, $usuarioId, $filtroExcluir); 
        if ($pregunta) return $pregunta;

        $pregunta = $this->buscarPorDificultad('neutra', $usuarioId, $filtroExcluir); 
        if ($pregunta) return $pregunta;

        $pregunta = $this->buscarTodas($usuarioId, $filtroExcluir);
        if ($pregunta) return $pregunta;

        $pregunta = $this->buscarPorDificultad($dificultadElegida, $usuarioId, $filtroSoloPartida);
        if ($pregunta) return $pregunta;

        return $this->buscarTodas($usuarioId, $filtroSoloPartida);
    }

    private function buscarPorDificultad($dificultad, $usuarioId, $filtroExclusion)
    {
        $order = ($dificultad === 'neutra')
            ? "dup.total_respuestas DESC, RAND()"
            : "RAND()";

        $sql = "SELECT p.*, c.nombre AS categoria_nombre, c.color AS categoria_color
                FROM preguntas p
                JOIN categorias c ON c.id = p.categoria_id
                LEFT JOIN dificultad_usuario_pregunta dup
                    ON dup.pregunta_id = p.id AND dup.usuario_id = $usuarioId
                WHERE p.activa = 1 AND $filtroExclusion
                  AND (dup.dificultad = '$dificultad' OR (dup.dificultad IS NULL AND '$dificultad' = 'neutra'))
                ORDER BY $order
                LIMIT 1";

        $result = $this->database->query($sql);
        return $result[0];
    }

    private function buscarTodas($usuarioId, $filtroExclusion)
    {
        $sql = "SELECT p.*, c.nombre AS categoria_nombre, c.color AS categoria_color
                FROM preguntas p
                JOIN categorias c ON c.id = p.categoria_id
                WHERE p.activa = 1 AND $filtroExclusion
                ORDER BY RAND() LIMIT 1";

        $result = $this->database->query($sql);
        return $result[0];
    }

    public function actualizarDificultad($preguntaId, $usuarioId, $esCorrecta) 
    {
        $sql = "INSERT INTO dificultad_usuario_pregunta (usuario_id, pregunta_id, total_respuestas, total_correctas, dificultad)
                VALUES ($usuarioId, $preguntaId, 1, $esCorrecta, 'neutra')
                ON DUPLICATE KEY UPDATE
                    total_respuestas = total_respuestas + 1,
                    total_correctas = total_correctas + $esCorrecta";
        $this->database->execute($sql);

        $result = $this->database->query(
            "SELECT total_respuestas, total_correctas FROM dificultad_usuario_pregunta
             WHERE usuario_id = $usuarioId AND pregunta_id = $preguntaId"
        );
        $row = $result[0];

        if ($row['total_respuestas'] >= $this->minRespuestasParaDificultad) {
            $ratio = (int)$row['total_correctas'] / (int)$row['total_respuestas'];
            if ($ratio > 0.7) {
                $dificultad = 'facil';
            } elseif ($ratio < 0.3) {
                $dificultad = 'dificil';
            } else {
                $dificultad = 'media';
            }
            $this->database->execute(
                "UPDATE dificultad_usuario_pregunta SET dificultad = '$dificultad'
                 WHERE usuario_id = $usuarioId AND pregunta_id = $preguntaId"
            );
        }
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

    private function calcularNivelUsuario($usuarioId)
    {
        $sql = "SELECT COUNT(*) AS total,
                       IFNULL(SUM(es_correcta), 0) AS correctas
                FROM partidas_preguntas pp
                JOIN partidas p ON p.id = pp.partida_id
                WHERE p.usuario_id = $usuarioId";
        $result = $this->database->query($sql);
        $total = (int)$result[0]['total'];

        if ($total < $this->minRespuestasParaDificultad) {
            return 0.5;
        }

        return (int)$result[0]['correctas'] / $total;
    }

    private function obtenerIdsVistosPorUsuario($usuarioId) 
    {
        $result = $this->database->query( 
            "SELECT DISTINCT pp.pregunta_id FROM partidas_preguntas pp
             JOIN partidas p ON p.id = pp.partida_id
             WHERE p.usuario_id = $usuarioId"
        );
        return array_column($result, 'pregunta_id');
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
        return $result[0];
    }

    public function getCategorias()
    {
        return $this->database->query("SELECT * FROM categorias");
    }
}
