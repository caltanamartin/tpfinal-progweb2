<?php

class MyDatabase
{
    private $conexion;

    public function __construct($hostname, $username, $password, $database)
    {
        $this->conexion = new mysqli($hostname, $username, $password, $database);
        $this->conexion->set_charset("utf8mb4");
    }

    public function query($sql)
    {
        $result = $this->conexion->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function execute($sql)
    {
        $this->conexion->query($sql);
        return $this->conexion->affected_rows;
    }

    public function lastInsertId()
    {
        return $this->conexion->insert_id;
    }

    public function escape($str)
    {
        return $this->conexion->real_escape_string($str);
    }

    public function executePrepared($sql, $params = [])
    {
        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) {
            return false;
        }
        if (!empty($params)) {
            $types = '';
            foreach ($params as $p) {
                $types .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
            }
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected;
    }

    public function queryPrepared($sql, $params = [])
    {
        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) {
            return [];
        }
        if (!empty($params)) {
            $types = '';
            foreach ($params as $p) {
                $types .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
            }
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    }

    public function lastInsertIdPrepared()
    {
        return $this->conexion->insert_id;
    }

    public function __destruct()
    {
        $this->conexion->close();
    }
}
