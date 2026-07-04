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
        $filtroExcluir = !empty($idsEnPartida) ? "p.id NOT IN (" . implode(',', $idsEnPartida) . ")" : "1=1";
        $filtroCategoria = $categoriaId ? "AND p.categoria_id = $categoriaId" : "";

        $pregunta = $this->buscarPorDificultad($dificultadElegida, $filtroExcluir, $filtroCategoria);
        if ($pregunta) return $pregunta;

        $pregunta = $this->buscarPorDificultad('neutra', $filtroExcluir, $filtroCategoria);
        if ($pregunta) return $pregunta;

        return $this->buscarTodas($filtroExcluir, $filtroCategoria);
    }

    private function buscarPorDificultad($dificultad, $filtroExclusion, $filtroCategoria = '')
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
                WHERE p.activa = 1 AND $filtroExclusion $filtroCategoria
                GROUP BY p.id
                HAVING $having
                ORDER BY $order
                LIMIT 1";

        $result = $this->database->query($sql);
        return !empty($result) ? $result[0] : null;
    }

    private function buscarTodas($filtroExclusion, $filtroCategoria = '')
    {
        $sql = "SELECT p.*, c.nombre AS categoria_nombre, c.color AS categoria_color,
                       COUNT(pp.id) AS total_respuestas_global,
                       IFNULL(SUM(pp.es_correcta), 0) AS total_correctas_global
                FROM preguntas p
                JOIN categorias c ON c.id = p.categoria_id
                LEFT JOIN partidas_preguntas pp ON pp.pregunta_id = p.id
                WHERE p.activa = 1 AND $filtroExclusion $filtroCategoria
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

    public function getPreguntasActivas()
    {
        $sql = "SELECT COUNT(*) AS total FROM preguntas WHERE activa = 1";
        $result = $this->database->query($sql);
        return $result[0]['total'];
    }

    public function getTotalPreguntas($filtro = 'mes')
    {
        $intervalo = $this->intervaloSql($filtro);
        $sql = "SELECT COUNT(*) AS total FROM preguntas WHERE creado_en >= DATE_SUB(NOW(), INTERVAL $intervalo)";
        $result = $this->database->query($sql);
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
        return $this->database->query("SELECT * FROM categorias");
    }

    public function reportar($preguntaId, $usuarioId, $motivo)
    {
        $motivo = $this->database->escape($motivo ?: '');
        $sql = "INSERT INTO reportes_preguntas (pregunta_id, usuario_id, motivo)
                VALUES ($preguntaId, $usuarioId, '$motivo')";
        return $this->database->execute($sql);
    }

    public function crear($categoriaId, $pregunta, $opcionA, $opcionB, $opcionC, $opcionD, $respuestaCorrecta, $creadorId = null, $rol = 'usuario')
    {
        $activa = ($rol === 'editor' || $rol === 'admin') ? 1 : 0;
        $creador = $creadorId ?: 'NULL';
        $sql = "INSERT INTO preguntas (categoria_id, pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, activa, creador_id)
                VALUES ($categoriaId, '$pregunta', '$opcionA', '$opcionB', '$opcionC', '$opcionD', '$respuestaCorrecta', $activa, $creador)";
        return $this->database->execute($sql);
    }

    public function listarTodas($pagina = null, $porPagina = null)
    {
        $sql = "SELECT p.*, c.nombre AS categoria_nombre, c.color AS categoria_color,
                       u.username AS creador_username
                FROM preguntas p
                JOIN categorias c ON c.id = p.categoria_id
                LEFT JOIN usuarios u ON u.id = p.creador_id
                ORDER BY p.id DESC";
        if ($pagina && $porPagina) {
            $offset = ($pagina - 1) * $porPagina;
            $total = $this->database->query("SELECT COUNT(*) AS total FROM preguntas")[0]['total'];
            $sql .= " LIMIT $porPagina OFFSET $offset";
            return ['filas' => $this->database->query($sql), 'total' => $total, 'paginas' => (int)ceil($total / $porPagina)];
        }
        return $this->database->query($sql);
    }

    public function obtener($id)
    {
        $sql = "SELECT p.*, c.nombre AS categoria_nombre
                FROM preguntas p
                JOIN categorias c ON c.id = p.categoria_id
                WHERE p.id = $id";
        $result = $this->database->query($sql);
        return !empty($result) ? $result[0] : null;
    }

    public function actualizar($id, $data)
    {
        $sets = [];
        foreach ($data as $campo => $valor) {
            $sets[] = "$campo = '$valor'";
        }
        $sql = "UPDATE preguntas SET " . implode(', ', $sets) . " WHERE id = $id";
        return $this->database->execute($sql);
    }

    public function desactivar($id, $editorId)
    {
        $sql = "UPDATE preguntas SET activa = 0, revisada_por = $editorId, revisada_en = NOW() WHERE id = $id";
        return $this->database->execute($sql);
    }

    public function listarReportes($pagina = null, $porPagina = null)
    {
        $sql = "SELECT rp.*, p.pregunta AS pregunta_texto, u.username AS reportado_por
                FROM reportes_preguntas rp
                JOIN preguntas p ON p.id = rp.pregunta_id
                JOIN usuarios u ON u.id = rp.usuario_id
                WHERE rp.accion IS NULL
                ORDER BY rp.creado_en DESC";
        if ($pagina && $porPagina) {
            $offset = ($pagina - 1) * $porPagina;
            $total = $this->database->query("SELECT COUNT(*) AS total FROM reportes_preguntas WHERE accion IS NULL")[0]['total'];
            $sql .= " LIMIT $porPagina OFFSET $offset";
            return ['filas' => $this->database->query($sql), 'total' => $total, 'paginas' => (int)ceil($total / $porPagina)];
        }
        return $this->database->query($sql);
    }

    public function resolverReporte($reporteId, $editorId, $accion)
    {
        $sql = "UPDATE reportes_preguntas SET resuelto_por = $editorId, resuelto_en = NOW(), accion = '$accion' WHERE id = $reporteId";
        return $this->database->execute($sql);
    }

    public function listarPendientes($pagina = null, $porPagina = null)
    {
        $sql = "SELECT p.*, c.nombre AS categoria_nombre, c.color AS categoria_color, u.username AS creador_username
                FROM preguntas p
                JOIN categorias c ON c.id = p.categoria_id
                LEFT JOIN usuarios u ON u.id = p.creador_id
                WHERE p.activa = 0 AND p.revisada_por IS NULL
                ORDER BY p.creado_en DESC";
        if ($pagina && $porPagina) {
            $offset = ($pagina - 1) * $porPagina;
            $total = $this->database->query("SELECT COUNT(*) AS total FROM preguntas WHERE activa = 0 AND revisada_por IS NULL")[0]['total'];
            $sql .= " LIMIT $porPagina OFFSET $offset";
            return ['filas' => $this->database->query($sql), 'total' => $total, 'paginas' => (int)ceil($total / $porPagina)];
        }
        return $this->database->query($sql);
    }

    public function aprobar($id, $editorId)
    {
        $sql = "UPDATE preguntas SET activa = 1, revisada_por = $editorId, revisada_en = NOW() WHERE id = $id";
        return $this->database->execute($sql);
    }

    public function eliminar($id)
    {
        $sql = "DELETE FROM preguntas WHERE id = $id";
        return $this->database->execute($sql);
    }
}
