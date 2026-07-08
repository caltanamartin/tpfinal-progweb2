<?php

class SqlHelper
{
    public static function intervaloSql($filtro)
    {
        return match ($filtro) {
            'dia' => '1 DAY',
            'semana' => '1 WEEK',
            'anio' => '1 YEAR',
            default => '1 MONTH',
        };
    }

    public static function formatoPeriodoSql($filtro, $columna)
    {
        return match ($filtro) {
            'dia' => "DATE_FORMAT($columna, '%Y-%m-%d %H:00')",
            'anio' => "DATE_FORMAT($columna, '%Y-%m')",
            default => "DATE($columna)",
        };
    }
}
