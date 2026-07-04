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
        $sql = "SELECT * FROM usuarios WHERE username = '$username'";
        $result = $this->database->query($sql);
        return !empty($result) ? $result[0] : null;
    }

    public function getUsuarioConEstadisticas($id)
    {
        $sql = "SELECT u.*,
                   IFNULL(SUM(p.puntaje), 0) AS puntaje_total,
                   COUNT(p.id) AS cantidad_partidas
            FROM usuarios u
            LEFT JOIN partidas p ON p.usuario_id = u.id AND p.estado = 'terminada'
            WHERE u.id = $id
            GROUP BY u.id";
        $result = $this->database->query($sql);
        return $result[0] ?? null;
    }

    public function getByEmail($email)
    {
        $sql = "SELECT * FROM usuarios WHERE email = '$email'";
        $result = $this->database->query($sql);
        return !empty($result) ? $result[0] : null;
    }

    public function crear($email, $nombre, $username, $password, $anioNacimiento, $sexo, $pais, $ciudad, $fotoPerfil)
    {
        $sql = "INSERT INTO usuarios (email, nombre, username, password, anio_nacimiento, sexo, pais, ciudad, foto_perfil)
                VALUES ('$email', '$nombre', '$username', '$password', '$anioNacimiento', '$sexo', '$pais', '$ciudad', '$fotoPerfil')";
        return $this->database->execute($sql);
    }

    public function saveToken($id, $token)
    {
        $sql = "UPDATE usuarios SET token_verificacion = '$token' WHERE id = $id";
        return $this->database->execute($sql);
    }

    public function findByToken($token)
    {
        $sql = "SELECT * FROM usuarios WHERE token_verificacion = '$token'";
        $result = $this->database->query($sql);
        return $result[0] ?? null;
    }

    public function setVerificado($id)
    {
        $sql = "UPDATE usuarios SET verificado = 1, token_verificacion = NULL WHERE id = $id";
        return $this->database->execute($sql);
    }

    public function getRanking($limite = 50, $rolFiltro = null)
    {
        $sql = "SELECT u.id, u.username, u.nombre, u.foto_perfil, u.rol,
                       IFNULL(SUM(p.puntaje), 0) AS puntaje_total,
                       COUNT(p.id) AS cantidad_partidas
                FROM usuarios u
                LEFT JOIN partidas p ON p.usuario_id = u.id AND p.estado = 'terminada'
                WHERE u.verificado = 1";
        if ($rolFiltro === 'usuario') {
            $sql .= " AND u.rol = 'usuario'";
        }
        $sql .= " GROUP BY u.id
                  ORDER BY puntaje_total DESC
                  LIMIT $limite";
        return $this->database->query($sql);
    }

    public function getTotalJugadores($filtro = 'mes')
    {
        $intervalo = $this->intervaloSql($filtro);
        $sql = "SELECT COUNT(*) AS total FROM usuarios WHERE verificado = 1 AND creado_en >= DATE_SUB(NOW(), INTERVAL $intervalo)";
        $result = $this->database->query($sql);
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
        return $this->database->query($sql);
    }

    public function getUsuariosPorPais($filtro = 'mes')
    {
        $intervalo = $this->intervaloSql($filtro);
        $sql = "SELECT pais, COUNT(*) AS total
                FROM usuarios
                WHERE pais != '' AND creado_en >= DATE_SUB(NOW(), INTERVAL $intervalo)
                GROUP BY pais ORDER BY total DESC";
        return $this->database->query($sql);
    }

    public function getUsuariosPorRol()
    {
        $sql = "SELECT rol, COUNT(*) AS total
                FROM usuarios
                GROUP BY rol";
        return $this->database->query($sql);
    }

    public function getUsuariosPorSexo($filtro = 'mes')
    {
        $intervalo = $this->intervaloSql($filtro);
        $sql = "SELECT sexo, COUNT(*) AS total
                FROM usuarios
                WHERE creado_en >= DATE_SUB(NOW(), INTERVAL $intervalo)
                GROUP BY sexo";
        return $this->database->query($sql);
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
        return $this->database->query($sql);
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

    public function getAll($pagina = null, $porPagina = null)
    {
        $sql = "SELECT id, email, nombre, username, anio_nacimiento, sexo, pais, ciudad, foto_perfil, verificado, rol, creado_en FROM usuarios ORDER BY id";
        if ($pagina && $porPagina) {
            $offset = ($pagina - 1) * $porPagina;
            $sql .= " LIMIT $porPagina OFFSET $offset";
            $total = $this->database->query("SELECT COUNT(*) AS total FROM usuarios")[0]['total'];
            return ['filas' => $this->database->query($sql), 'total' => $total, 'paginas' => (int)ceil($total / $porPagina)];
        }
        return $this->database->query($sql);
    }

    public function actualizar($username, $cambios)
    {
        if (empty($cambios)) {
            return 0;
        }

        $sets = [];
        foreach ($cambios as $campo => $valor) {
            $sets[] = "$campo = '$valor'";
        }

        $sql = "UPDATE usuarios SET " . implode(', ', $sets) . " WHERE username = '$username'";
        return $this->database->execute($sql);
    }
}
