<?php

class PreguntaModel
{
    private $database;
    private $minRespuestasGlobales = 10;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getPreguntaAleatoria($partidaId, $usuarioId, $categoriaId = null)
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

        $pregunta = $this->buscarPorDificultad($dificultadElegida, $idsEnPartida, $categoriaId);
        if ($pregunta) return $pregunta;

        $pregunta = $this->buscarPorDificultad('neutra', $idsEnPartida, $categoriaId);
        if ($pregunta) return $pregunta;

        return $this->buscarTodas($idsEnPartida, $categoriaId);
    }

    private function buscarPorDificultad($dificultad, $idsEnPartida, $categoriaId = null)
    {
        $params = [];

        if ($dificultad === 'facil') {
            $having = "total_respuestas_global >= ? AND (total_correctas_global / total_respuestas_global) > 0.7";
            $params[] = $this->minRespuestasGlobales;
        } elseif ($dificultad === 'dificil') {
            $having = "total_respuestas_global >= ? AND (total_correctas_global / total_respuestas_global) < 0.3";
            $params[] = $this->minRespuestasGlobales;
        } elseif ($dificultad === 'media') {
            $having = "total_respuestas_global >= ? AND (total_correctas_global / total_respuestas_global) BETWEEN 0.3 AND 0.7";
            $params[] = $this->minRespuestasGlobales;
        } else {
            $having = "? IS NULL OR total_respuestas_global < ?";
            $params[] = null;
            $params[] = $this->minRespuestasGlobales;
        }

        $where = "p.activa = 1";
        if (!empty($idsEnPartida)) {
            $placeholders = implode(',', array_fill(0, count($idsEnPartida), '?'));
            $where .= " AND p.id NOT IN ($placeholders)";
            $params = array_merge($params, $idsEnPartida);
        }
        if ($categoriaId) {
            $where .= " AND p.categoria_id = ?";
            $params[] = $categoriaId;
        }

        $order = ($dificultad === 'neutra')
            ? "total_respuestas_global DESC, RAND()"
            : "RAND()";

        $sql = "SELECT p.*, c.nombre AS categoria_nombre, c.color AS categoria_color,
                       COUNT(pp.id) AS total_respuestas_global,
                       IFNULL(SUM(pp.es_correcta), 0) AS total_correctas_global
                FROM preguntas p
                JOIN categorias c ON c.id = p.categoria_id
                LEFT JOIN partidas_preguntas pp ON pp.pregunta_id = p.id
                WHERE $where
                GROUP BY p.id
                HAVING $having
                ORDER BY $order
                LIMIT 1";

        $result = $this->database->queryPrepared($sql, $params);
        return !empty($result) ? $result[0] : null;
    }

    private function buscarTodas($idsEnPartida, $categoriaId = null)
    {
        $params = [];

        $where = "p.activa = 1";
        if (!empty($idsEnPartida)) {
            $placeholders = implode(',', array_fill(0, count($idsEnPartida), '?'));
            $where .= " AND p.id NOT IN ($placeholders)";
            $params = $idsEnPartida;
        }
        if ($categoriaId) {
            $where .= " AND p.categoria_id = ?";
            $params[] = $categoriaId;
        }

        $sql = "SELECT p.*, c.nombre AS categoria_nombre, c.color AS categoria_color,
                       COUNT(pp.id) AS total_respuestas_global,
                       IFNULL(SUM(pp.es_correcta), 0) AS total_correctas_global
                FROM preguntas p
                JOIN categorias c ON c.id = p.categoria_id
                LEFT JOIN partidas_preguntas pp ON pp.pregunta_id = p.id
                WHERE $where
                GROUP BY p.id
                ORDER BY RAND() LIMIT 1";

        $result = $this->database->queryPrepared($sql, $params);
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
                WHERE p.usuario_id = ? AND pp.respuesta IS NOT NULL";
        $result = $this->database->queryPrepared($sql, [$usuarioId]);
        $total = (int)$result[0]['total'];

        if ($total < 5) {
            return 0.5;
        }

        return (int)$result[0]['correctas'] / $total;
    }

    private function obtenerIdsVistosEnPartida($partidaId)
    {
        $result = $this->database->queryPrepared(
            "SELECT pregunta_id FROM partidas_preguntas WHERE partida_id = ?",
            [$partidaId]
        );
        return array_column($result, 'pregunta_id');
    }

    public function getPreguntaConCategoria($preguntaId)
    {
        $sql = "SELECT p.*, c.nombre AS categoria_nombre, c.color AS categoria_color
                FROM preguntas p
                JOIN categorias c ON c.id = p.categoria_id
                WHERE p.id = ?";
        $result = $this->database->queryPrepared($sql, [$preguntaId]);
        return !empty($result) ? $result[0] : null;
    }

    public function getPreguntasActivas()
    {
        $result = $this->database->queryPrepared("SELECT COUNT(*) AS total FROM preguntas WHERE activa = 1");
        return $result[0]['total'];
    }

    public function getTotalPreguntas($filtro = 'mes')
    {
        $intervalo = $this->intervaloSql($filtro);
        $sql = "SELECT COUNT(*) AS total FROM preguntas WHERE creado_en >= DATE_SUB(NOW(), INTERVAL $intervalo)";
        $result = $this->database->queryPrepared($sql);
        return $result[0]['total'];
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

    public function getCategorias()
    {
        return $this->database->queryPrepared("SELECT * FROM categorias");
    }

    public function reportar($preguntaId, $usuarioId, $motivo)
    {
        $sql = "INSERT INTO reportes_preguntas (pregunta_id, usuario_id, motivo)
                VALUES (?, ?, ?)";
        return $this->database->executePrepared($sql, [$preguntaId, $usuarioId, $motivo ?: '']);
    }

    public function crear($categoriaId, $pregunta, $opcionA, $opcionB, $opcionC, $opcionD, $respuestaCorrecta, $creadorId = null, $rol = 'usuario')
    {
        $activa = ($rol === 'editor' || $rol === 'admin') ? 1 : 0;
        $sql = "INSERT INTO preguntas (categoria_id, pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, activa, creador_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        return $this->database->executePrepared($sql, [$categoriaId, $pregunta, $opcionA, $opcionB, $opcionC, $opcionD, $respuestaCorrecta, $activa, $creadorId]);
    }

    public function listarTodas($pagina = null, $porPagina = null)
    {
        if ($pagina && $porPagina) {
            $offset = ($pagina - 1) * $porPagina;
            $sql = "SELECT p.*, c.nombre AS categoria_nombre, c.color AS categoria_color,
                           u.username AS creador_username
                    FROM preguntas p
                    JOIN categorias c ON c.id = p.categoria_id
                    LEFT JOIN usuarios u ON u.id = p.creador_id
                    ORDER BY p.id DESC LIMIT ? OFFSET ?";
            $total = $this->database->queryPrepared("SELECT COUNT(*) AS total FROM preguntas")[0]['total'];
            return ['filas' => $this->database->queryPrepared($sql, [$porPagina, $offset]), 'total' => $total, 'paginas' => (int)ceil($total / $porPagina)];
        }
        $sql = "SELECT p.*, c.nombre AS categoria_nombre, c.color AS categoria_color,
                       u.username AS creador_username
                FROM preguntas p
                JOIN categorias c ON c.id = p.categoria_id
                LEFT JOIN usuarios u ON u.id = p.creador_id
                ORDER BY p.id DESC";
        return $this->database->queryPrepared($sql);
    }

    public function obtener($id)
    {
        $sql = "SELECT p.*, c.nombre AS categoria_nombre
                FROM preguntas p
                JOIN categorias c ON c.id = p.categoria_id
                WHERE p.id = ?";
        $result = $this->database->queryPrepared($sql, [$id]);
        return !empty($result) ? $result[0] : null;
    }

    public function actualizar($id, $data)
    {
        $parts = [];
        $params = [];
        foreach ($data as $campo => $valor) {
            $parts[] = "$campo = ?";
            $params[] = $valor;
        }
        $sql = "UPDATE preguntas SET " . implode(', ', $parts) . " WHERE id = ?";
        $params[] = $id;
        return $this->database->executePrepared($sql, $params);
    }

    public function desactivar($id, $editorId)
    {
        $sql = "UPDATE preguntas SET activa = 0, revisada_por = ?, revisada_en = NOW() WHERE id = ?";
        return $this->database->executePrepared($sql, [$editorId, $id]);
    }

    public function listarReportes($pagina = null, $porPagina = null)
    {
        if ($pagina && $porPagina) {
            $offset = ($pagina - 1) * $porPagina;
            $sql = "SELECT rp.*, p.pregunta AS pregunta_texto, u.username AS reportado_por
                    FROM reportes_preguntas rp
                    JOIN preguntas p ON p.id = rp.pregunta_id
                    JOIN usuarios u ON u.id = rp.usuario_id
                    WHERE rp.accion IS NULL
                    ORDER BY rp.creado_en DESC LIMIT ? OFFSET ?";
            $total = $this->database->queryPrepared("SELECT COUNT(*) AS total FROM reportes_preguntas WHERE accion IS NULL")[0]['total'];
            return ['filas' => $this->database->queryPrepared($sql, [$porPagina, $offset]), 'total' => $total, 'paginas' => (int)ceil($total / $porPagina)];
        }
        $sql = "SELECT rp.*, p.pregunta AS pregunta_texto, u.username AS reportado_por
                FROM reportes_preguntas rp
                JOIN preguntas p ON p.id = rp.pregunta_id
                JOIN usuarios u ON u.id = rp.usuario_id
                WHERE rp.accion IS NULL
                ORDER BY rp.creado_en DESC";
        return $this->database->queryPrepared($sql);
    }

    public function resolverReporte($reporteId, $editorId, $accion)
    {
        $sql = "UPDATE reportes_preguntas SET resuelto_por = ?, resuelto_en = NOW(), accion = ? WHERE id = ?";
        return $this->database->executePrepared($sql, [$editorId, $accion, $reporteId]);
    }

    public function listarPendientes($pagina = null, $porPagina = null)
    {
        if ($pagina && $porPagina) {
            $offset = ($pagina - 1) * $porPagina;
            $sql = "SELECT p.*, c.nombre AS categoria_nombre, c.color AS categoria_color, u.username AS creador_username
                    FROM preguntas p
                    JOIN categorias c ON c.id = p.categoria_id
                    LEFT JOIN usuarios u ON u.id = p.creador_id
                    WHERE p.activa = 0 AND p.revisada_por IS NULL
                    ORDER BY p.creado_en DESC LIMIT ? OFFSET ?";
            $total = $this->database->queryPrepared("SELECT COUNT(*) AS total FROM preguntas WHERE activa = 0 AND revisada_por IS NULL")[0]['total'];
            return ['filas' => $this->database->queryPrepared($sql, [$porPagina, $offset]), 'total' => $total, 'paginas' => (int)ceil($total / $porPagina)];
        }
        $sql = "SELECT p.*, c.nombre AS categoria_nombre, c.color AS categoria_color, u.username AS creador_username
                FROM preguntas p
                JOIN categorias c ON c.id = p.categoria_id
                LEFT JOIN usuarios u ON u.id = p.creador_id
                WHERE p.activa = 0 AND p.revisada_por IS NULL
                ORDER BY p.creado_en DESC";
        return $this->database->queryPrepared($sql);
    }

    public function aprobar($id, $editorId)
    {
        $sql = "UPDATE preguntas SET activa = 1, revisada_por = ?, revisada_en = NOW() WHERE id = ?";
        return $this->database->executePrepared($sql, [$editorId, $id]);
    }

    public function eliminar($id)
    {
        $sql = "DELETE FROM preguntas WHERE id = ?";
        return $this->database->executePrepared($sql, [$id]);
    }
}
