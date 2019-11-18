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

    public static function valueEmpty($array,$includeField=[],$excludeField=[]) 
    {
        $isEmpty = true;
        if (count($includeField)==0) $includeField = array_keys($array);
        foreach($includeField as $field) {
            if (!in_array($field,$excludeField)) {
                $isEmpty = $isEmpty & empty($array[$field]);
            }
        }
        return $isEmpty;
    }
}
