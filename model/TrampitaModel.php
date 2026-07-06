<?php

class TrampitaModel
{
    private $database;

    private $precios = [
        '50/50' => 1,
        'skip' => 1,
        'congelar_tiempo' => 1,
    ];

    private $nombres = [
        '50/50' => '50/50',
        'skip' => 'Saltear',
        'congelar_tiempo' => 'Congelar tiempo',
    ];

    private $descripciones = [
        '50/50' => 'Elimina dos opciones incorrectas',
        'skip' => 'Salteá esta pregunta sin perder',
        'congelar_tiempo' => 'Sumá 15 segundos al timer',
    ];

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getTipos()
    {
        $tipos = [];
        foreach ($this->precios as $tipo => $precio) {
            $tipos[] = [
                'tipo' => $tipo,
                'nombre' => $this->nombres[$tipo],
                'descripcion' => $this->descripciones[$tipo],
                'precio' => $precio,
            ];
        }
        return $tipos;
    }

    public function getPrecio($tipo)
    {
        return $this->precios[$tipo] ?? 1;
    }

    public function getNombre($tipo)
    {
        return $this->nombres[$tipo] ?? $tipo;
    }

    public function comprar($usuarioId, $tipo)
    {
        $sql = "INSERT INTO trampitas_compradas (usuario_id, tipo) VALUES (?, ?)";
        return $this->database->executePrepared($sql, [$usuarioId, $tipo]);
    }

    public function obtenerDisponibles($usuarioId)
    {
        $sql = "SELECT id, tipo, estado, comprada_en
                FROM trampitas_compradas
                WHERE usuario_id = ? AND estado = 'disponible'
                ORDER BY comprada_en ASC";
        return $this->database->queryPrepared($sql, [$usuarioId]);
    }

    public function contarDisponibles($usuarioId)
    {
        $sql = "SELECT tipo, COUNT(*) AS cantidad
                FROM trampitas_compradas
                WHERE usuario_id = ? AND estado = 'disponible'
                GROUP BY tipo";
        $rows = $this->database->queryPrepared($sql, [$usuarioId]);
        $result = ['50/50' => 0, 'skip' => 0, 'congelar_tiempo' => 0];
        foreach ($rows as $row) {
            $result[$row['tipo']] = (int)$row['cantidad'];
        }
        return $result;
    }

    public function usar($trampitaId, $partidaId)
    {
        $sql = "UPDATE trampitas_compradas
                SET estado = 'usado', usada_en = NOW(), partida_id = ?
                WHERE id = ? AND estado = 'disponible'";
        return $this->database->executePrepared($sql, [$partidaId, $trampitaId]);
    }

    public function obtener($id)
    {
        $sql = "SELECT * FROM trampitas_compradas WHERE id = ?";
        $result = $this->database->queryPrepared($sql, [$id]);
        return !empty($result) ? $result[0] : null;
    }

    public function getEstadisticas()
    {
        $sql = "SELECT tipo, COUNT(*) AS cantidad
                FROM trampitas_compradas
                GROUP BY tipo";
        $rows = $this->database->queryPrepared($sql, []);

        $totalRecaudado = 0;
        $totalCompras = 0;
        $porTipo = [];

        foreach ($this->precios as $tipo => $precio) {
            $porTipo[$tipo] = [
                'tipo' => $tipo,
                'nombre' => $this->nombres[$tipo],
                'precio' => $precio,
                'cantidad' => 0,
                'recaudado' => 0,
            ];
        }

        foreach ($rows as $row) {
            $tipo = $row['tipo'];
            if (isset($porTipo[$tipo])) {
                $cantidad = (int)$row['cantidad'];
                $porTipo[$tipo]['cantidad'] = $cantidad;
                $porTipo[$tipo]['recaudado'] = $cantidad * $this->precios[$tipo];
            }
            $totalCompras += (int)$row['cantidad'];
        }

        foreach ($porTipo as $t) {
            $totalRecaudado += $t['recaudado'];
        }

        return [
            'totalCompras' => $totalCompras,
            'totalRecaudado' => $totalRecaudado,
            'porTipo' => array_values($porTipo),
        ];
    }

    public function listarTodas($offset = 0, $limit = 50, $tipoFiltro = null)
    {
        $sql = "SELECT tc.*, u.username, u.nombre AS usuario_nombre
                FROM trampitas_compradas tc
                JOIN usuarios u ON u.id = tc.usuario_id";
        $params = [];

        if ($tipoFiltro && in_array($tipoFiltro, ['50/50', 'skip', 'congelar_tiempo'])) {
            $sql .= " WHERE tc.tipo = ?";
            $params[] = $tipoFiltro;
        }

        $sql .= " ORDER BY tc.comprada_en DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->database->queryPrepared($sql, $params);
    }

    public function contarTodas($tipoFiltro = null)
    {
        $sql = "SELECT COUNT(*) AS total FROM trampitas_compradas tc";
        $params = [];

        if ($tipoFiltro && in_array($tipoFiltro, ['50/50', 'skip', 'congelar_tiempo'])) {
            $sql .= " WHERE tc.tipo = ?";
            $params[] = $tipoFiltro;
        }

        $result = $this->database->queryPrepared($sql, $params);
        return !empty($result) ? (int)$result[0]['total'] : 0;
    }
}
