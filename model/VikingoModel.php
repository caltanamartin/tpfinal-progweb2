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
        $sql = "SELECT * FROM guerreros WHERE id = $id";
        Log::info("SQL: $sql");
        $filas = $this->database->query($sql);
        return !empty($filas) ? $filas[0] : null;
    }

    public function alta($nombre, $apodo, $clan, $fuerza)
    {
        $sql = "INSERT INTO guerreros (nombre, apodo, clan, fuerza) VALUES ('$nombre', '$apodo', '$clan', $fuerza)";
        Log::info("SQL: $sql");
        return $this->database->execute($sql);
    }

    public function editar($id, $nombre, $apodo, $clan, $fuerza)
    {
        $sql = "UPDATE guerreros SET nombre = '$nombre', apodo = '$apodo', clan = '$clan', fuerza = $fuerza WHERE id = $id";
        Log::info("SQL: $sql");
        $this->database->execute($sql);
    }

    public function eliminar($id)
    {
        $sql = "DELETE FROM guerreros WHERE id = $id";
        Log::info("SQL: $sql");
        $this->database->execute($sql);
    }
}
