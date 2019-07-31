<?php

namespace App\Http\Controllers\Master;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class IdController extends Controller
{
    public static function CheckDigit($number) {
      if(is_numeric($number)) {
        $number = $number . '0';
        $sum = 0;
        $i = strlen($number);
        $odd_length = $i % 2;
        while ($i-- > 0) {
          $sum+=$number[$i];
          ($odd_length == ($i % 2)) ? ($number[$i] > 4) ? ($sum+=($number[$i] - 9)) : ($sum+=$number[$i]) : false;
        }
        return (10 - ($sum % 10)) % 10;
      } else {
        return 0;
      }
    }

    public static function validateCheckDigit($number) {
      if(is_numeric($number)) {
        $sum = 0;
        $numDigits = strlen($number) - 1;
        $parity = $numDigits % 2;
        for ($i = $numDigits; $i >= 0; $i--) {
            $digit = substr($number, $i, 1);
            if (!$parity == ($i % 2)) {
                $digit <<= 1;
            }
            $digit = ($digit > 9) ? ($digit - 9) : $digit;
            $sum += $digit;
        }
        return (0 == ($sum % 10));
      } else {
        return false;
      }
    }

    public static function issueId($idType,$prefix,$numberLength=6,$spacerChar='',$addCheckDigit=true) {
      $prefix = date($prefix);
      $masterid = \App\Models\Master\MasterIds::where([['idType','=',$idType],['prefix','=',$prefix]])->first();
      if ($masterid == null) {
        $masterid = new \App\Models\Master\MasterIds;
        $masterid->idType = $idType;
        $masterid->prefix = $prefix;
        $masterid->runningNumber = 0;
        $masterid->save();
      }

      $masterid->runningNumber += 1;
      $masterid->save();

      $runningId = str_pad($masterid->runningNumber,$numberLength,'0',STR_PAD_LEFT);

      $generatedId[] = $prefix;
      $generatedId[] = $runningId;
      if ($addCheckDigit) $generatedId[] = IdController::CheckDigit($runningId);

      return implode($spacerChar,$generatedId);
    }
}
