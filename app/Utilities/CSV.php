<?php

namespace App\Utilities;

class CSV
{
    public static function CSVtoArray($file)
    {
      $csv = file($file,FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
      if(substr($csv[0],0,3)==chr(hexdec('EF')).chr(hexdec('BB')).chr(hexdec('BF'))) $csv[0] = substr($csv[0],3);
      $csv = array_map('str_getcsv', $csv);
      array_walk($csv, function(&$a) use ($csv) {
        array_walk($a,function(&$b) {
          if (trim($b)=="") $b = null;
          else $b = trim($b);
        });
        $a = array_combine($csv[0], $a);
      });
      array_shift($csv);

      return $csv;
    }
}
