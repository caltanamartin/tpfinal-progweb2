<?php

class Validator
{
    public static function positiveInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $int = (int)$value;
        return $int > 0 ? $int : null;
    }

    public static function int($value, int $min = 0): int
    {
        return max($min, (int)$value);
    }
}
