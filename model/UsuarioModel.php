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
        $sql = "SELECT id, email, nombre, username, password, anio_nacimiento, sexo, pais, ciudad,
                       foto_perfil, verificado, token_verificacion, rol, creado_en
                FROM usuarios WHERE username = ?";
        $result = $this->database->queryPrepared($sql, [$username]);
        return !empty($result) ? $result[0] : null;
    }

    public function getUsuarioConEstadisticas($id)
    {
        $sql = "SELECT u.*,
                   IFNULL(SUM(p.puntaje), 0) AS puntaje_total,
                   COUNT(p.id) AS cantidad_partidas
            FROM usuarios u
            LEFT JOIN partidas p ON p.usuario_id = u.id AND p.estado = 'terminada'
            WHERE u.id = ?
            GROUP BY u.id";
        $result = $this->database->queryPrepared($sql, [$id]);
        return $result[0] ?? null;
    }

    public function getByEmail($email)
    {
        $sql = "SELECT * FROM usuarios WHERE email = ?";
        $result = $this->database->queryPrepared($sql, [$email]);
        return !empty($result) ? $result[0] : null;
    }

    public function crear($email, $nombre, $username, $password, $anioNacimiento, $sexo, $pais, $ciudad, $fotoPerfil)
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO usuarios (email, nombre, username, password, anio_nacimiento, sexo, pais, ciudad, foto_perfil)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        return $this->database->executePrepared($sql, [$email, $nombre, $username, $hash, $anioNacimiento, $sexo, $pais, $ciudad, $fotoPerfil]);
    }

    public function saveToken($id, $token)
    {
        $sql = "UPDATE usuarios SET token_verificacion = ? WHERE id = ?";
        return $this->database->executePrepared($sql, [$token, $id]);
    }

    public function findByToken($token)
    {
        $sql = "SELECT * FROM usuarios WHERE token_verificacion = ?";
        $result = $this->database->queryPrepared($sql, [$token]);
        return $result[0] ?? null;
    }

    public function setVerificado($id)
    {
        $sql = "UPDATE usuarios SET verificado = 1, token_verificacion = NULL WHERE id = ?";
        return $this->database->executePrepared($sql, [$id]);
    }

    public function getRanking($limite = 50, $rolFiltro = null)
    {
        $sql = "SELECT u.id, u.username, u.nombre, u.foto_perfil, u.rol,
                       IFNULL(SUM(p.puntaje), 0) AS puntaje_total,
                       COUNT(p.id) AS cantidad_partidas
                FROM usuarios u
                LEFT JOIN partidas p ON p.usuario_id = u.id AND p.estado = 'terminada'
                WHERE u.verificado = 1";
        $params = [];
        if ($rolFiltro === 'usuario') {
            $sql .= " AND u.rol = ?";
            $params[] = 'usuario';
        }
        $sql .= " GROUP BY u.id
                  ORDER BY puntaje_total DESC
                  LIMIT ?";
        $params[] = $limite;
        return $this->database->queryPrepared($sql, $params);
    }

    public function getTotalJugadores($filtro = 'mes')
    {
        $intervalo = $this->intervaloSql($filtro);
        $sql = "SELECT COUNT(*) AS total FROM usuarios WHERE verificado = 1 AND creado_en >= DATE_SUB(NOW(), INTERVAL $intervalo)";
        $result = $this->database->queryPrepared($sql);
        return $result[0]['total'];
    }

    public function getUsuariosNuevosPorPeriodo($filtro = 'mes')
    {
        $intervalo = $this->intervaloSql($filtro);
        $formato = $this->formatoPeriodoSql($filtro, 'creado_en');
        $sql = "SELECT $formato AS periodo, COUNT(*) AS total
                FROM usuarios
                WHERE creado_en >= DATE_SUB(NOW(), INTERVAL $intervalo)
                GROUP BY periodo ORDER BY periodo";
        return $this->database->queryPrepared($sql);
    }

    public function getUsuariosPorPais($filtro = 'mes')
    {
        $intervalo = $this->intervaloSql($filtro);
        $sql = "SELECT pais, COUNT(*) AS total
                FROM usuarios
                WHERE pais != '' AND creado_en >= DATE_SUB(NOW(), INTERVAL $intervalo)
                GROUP BY pais ORDER BY total DESC";
        return $this->database->queryPrepared($sql);
    }

    public function getUsuariosPorRol()
    {
        $sql = "SELECT rol, COUNT(*) AS total
                FROM usuarios
                GROUP BY rol";
        return $this->database->queryPrepared($sql);
    }

    public function getUsuariosPorSexo($filtro = 'mes')
    {
        $intervalo = $this->intervaloSql($filtro);
        $sql = "SELECT sexo, COUNT(*) AS total
                FROM usuarios
                WHERE creado_en >= DATE_SUB(NOW(), INTERVAL $intervalo)
                GROUP BY sexo";
        return $this->database->queryPrepared($sql);
    }

    public function getUsuariosPorEdad($filtro = 'mes')
    {
        $intervalo = $this->intervaloSql($filtro);
        $sql = "SELECT
                    CASE
                        WHEN anio_nacimiento IS NULL THEN 'Sin especificar'
                        WHEN TIMESTAMPDIFF(YEAR, anio_nacimiento, CURDATE()) < 18 THEN 'Menores'
                        WHEN TIMESTAMPDIFF(YEAR, anio_nacimiento, CURDATE()) >= 65 THEN 'Jubilados'
                        ELSE 'Medio'
                    END AS grupo_etario,
                    COUNT(*) AS total
                FROM usuarios
                WHERE creado_en >= DATE_SUB(NOW(), INTERVAL $intervalo)
                GROUP BY grupo_etario
                ORDER BY FIELD(grupo_etario, 'Menores', 'Medio', 'Jubilados', 'Sin especificar')";
        return $this->database->queryPrepared($sql);
    }

    public function getPorcentajeCorrectasPorUsuario($filtro = 'mes')
    {
        $intervalo = $this->intervaloSql($filtro);
        $sql = "SELECT u.id, u.username, u.nombre, u.foto_perfil,
                       COUNT(pp.id) AS total_respuestas,
                       IFNULL(SUM(pp.es_correcta), 0) AS respuestas_correctas,
                       IFNULL(ROUND(SUM(pp.es_correcta) / COUNT(pp.id) * 100, 1), 0) AS porcentaje
                FROM usuarios u
                JOIN partidas p ON p.usuario_id = u.id AND p.estado = 'terminada'
                JOIN partidas_preguntas pp ON pp.partida_id = p.id
                WHERE u.verificado = 1 AND pp.respondida_en >= DATE_SUB(NOW(), INTERVAL $intervalo)
                GROUP BY u.id
                ORDER BY porcentaje DESC";
        return $this->database->queryPrepared($sql);
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

    public function getAll($pagina = null, $porPagina = null)
    {
        if ($pagina && $porPagina) {
            $offset = ($pagina - 1) * $porPagina;
            $sql = "SELECT id, email, nombre, username, anio_nacimiento, sexo, pais, ciudad, foto_perfil, verificado, rol, creado_en FROM usuarios ORDER BY id LIMIT ? OFFSET ?";
            $total = $this->database->queryPrepared("SELECT COUNT(*) AS total FROM usuarios")[0]['total'];
            return ['filas' => $this->database->queryPrepared($sql, [$porPagina, $offset]), 'total' => $total, 'paginas' => (int)ceil($total / $porPagina)];
        }
        $sql = "SELECT id, email, nombre, username, anio_nacimiento, sexo, pais, ciudad, foto_perfil, verificado, rol, creado_en FROM usuarios ORDER BY id";
        return $this->database->queryPrepared($sql);
    }

    public function actualizar($username, $cambios)
    {
        if (empty($cambios)) {
            return 0;
        }

        $parts = [];
        $params = [];
        foreach ($cambios as $campo => $valor) {
            $parts[] = "$campo = ?";
            $params[] = $valor;
        }

        $sql = "UPDATE usuarios SET " . implode(', ', $parts) . " WHERE username = ?";
        $params[] = $username;
        return $this->database->executePrepared($sql, $params);
    }
}
