<?php

namespace App\Utilities;

class Base64
{
    public function isBase64($string)
    {
        return (bool)preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $string);
    }
}
