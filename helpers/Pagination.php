<?php

class Pagination
{
    public static function build($actual, $totalPaginas, $param, $hash)
    {
        $numeros = [];
        $inicio = max(1, $actual - 2);
        $fin = min($totalPaginas, $actual + 2);
        for ($i = $inicio; $i <= $fin; $i++) {
            $numeros[] = ['num' => $i, 'activa' => $i === $actual];
        }
        return [
            'mostrar' => $totalPaginas > 1,
            'actual' => $actual,
            'anterior' => $actual > 1,
            'siguiente' => $actual < $totalPaginas,
            'prevPage' => $actual - 1,
            'nextPage' => $actual + 1,
            'numeros' => $numeros,
            'param' => $param,
            'hash' => $hash,
        ];
    }
}
