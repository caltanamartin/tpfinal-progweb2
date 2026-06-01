<?php

class VikingoModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getVikingos()
    {
        $sql = "SELECT * FROM guerreros";
        Log::info("SQL: $sql");
        return $this->database->query($sql);
    }

    public function getVikingo($id)
    {
        $sql = "SELECT * FROM guerreros WHERE id = ?";
        Log::info("SQL: $sql [$id]");
        $filas = $this->database->query($sql, [$id]);
        return !empty($filas) ? $filas[0] : null;
    }

    public function alta($nombre, $apodo, $clan, $fuerza)
    {
        $sql = "INSERT INTO guerreros (nombre, apodo, clan, fuerza) VALUES (?, ?, ?, ?)";
        Log::info("SQL: $sql [$nombre, $apodo, $clan, $fuerza]");
        return $this->database->execute($sql, [$nombre, $apodo, $clan, $fuerza]);
    }

    public function editar($id, $nombre, $apodo, $clan, $fuerza)
    {
        $sql = "UPDATE guerreros SET nombre = ?, apodo = ?, clan = ?, fuerza = ? WHERE id = ?";
        Log::info("SQL: $sql [$nombre, $apodo, $clan, $fuerza, $id]");
        $this->database->execute($sql, [$nombre, $apodo, $clan, $fuerza, $id]);
    }

    public function eliminar($id)
    {
        $sql = "DELETE FROM guerreros WHERE id = ?";
        Log::info("SQL: $sql [$id]");
        $this->database->execute($sql, [$id]);
    }
}
