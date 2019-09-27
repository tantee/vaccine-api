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

    public static function keyExists($key, $array)
    {
        $result = array_key_exists($key, $array);
        if ($result) return $result;

        foreach ($array as $subarray) {
            if (is_array($subarray)) {
                    $result = self::keyExists($key, $subarray);
            }
            if ($result) return $result;
        }
        return $result;
    }
}
