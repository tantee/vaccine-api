<?php

namespace App\Utilities;

class ArrayType
{
    public static function isMultiDimension($array)
    {
        return count($array)!==count($array,COUNT_RECURSIVE);
    }

    public static function isAssociative($array)
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
