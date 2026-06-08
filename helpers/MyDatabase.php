<?php

class MyDatabase
{
    private $conexion;

    public function __construct($hostname, $username, $password, $database)
    {
        $this->conexion = new mysqli($hostname, $username, $password, $database);
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

    public function __destruct()
    {
        $this->conexion->close();
    }
}
