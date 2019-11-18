<?php

namespace App\Document;

use \App\Http\Controllers\Master\MasterController;

class Patient
{
  public static function Name($FieldName, &$CurrVal,&$CurrPrm) {
    if (\is_array($CurrVal)) {
      $tmpName = [];

      if (!empty($CurrVal['nameType']) && ($CurrVal['nameType']=='EN' || $CurrVal['nameType']=='ALIAS_EN' )) $English = true;
      else $English = false;

      if (!empty($CurrVal['namePrefix'])) $tmpName[] = MasterController::translateMaster('$NamePrefix',$CurrVal['namePrefix'],$English);
      if (!empty($CurrVal['firstName'])) $tmpName[] = $CurrVal['firstName'];
      if (!empty($CurrVal['middleName'])) $tmpName[] = $CurrVal['middleName'];
      if (!empty($CurrVal['lastName'])) $tmpName[] = $CurrVal['lastName'];
      if (!empty($CurrVal['nameSuffix'])) $tmpName[] = MasterController::translateMaster('$NameSuffix',$CurrVal['nameSuffix'],$English);

      $CurrVal = implode(" ",$tmpName);
    }
  }

  public static function Address($FieldName, &$CurrVal,&$CurrPrm) {
    if (\is_array($CurrVal)) {
      $tmpAddress = [];

      $isThai = $CurrVal['country'] == "TH";

      if (!empty($CurrVal['address'])) $tmpAddress[] = $CurrVal['address'];
      if (!empty($CurrVal['village'])) $tmpAddress[] = $CurrVal['village'];
      if (!empty($CurrVal['moo'])) $tmpAddress[] = $CurrVal['moo'];
      if (!empty($CurrVal['trok'])) $tmpAddress[] = $CurrVal['trok'];
      if (!empty($CurrVal['soi'])) $tmpAddress[] = $CurrVal['soi'];
      if (!empty($CurrVal['street'])) $tmpAddress[] = $CurrVal['street'];
      if (!empty($CurrVal['subdistrict'])) $tmpAddress[] = ($isThai) ? MasterController::translateMaster('$Subdistrict',$CurrVal['subdistrict']) : $CurrVal['subdistrict'];
      if (!empty($CurrVal['district'])) $tmpAddress[] = ($isThai) ? MasterController::translateMaster('$District',$CurrVal['district']) : $CurrVal['district'];;
      if (!empty($CurrVal['province'])) $tmpAddress[] = ($isThai) ? MasterController::translateMaster('$Province',$CurrVal['province']) : $CurrVal['province'];
      if (!empty($CurrVal['country'])) $tmpAddress[] = MasterController::translateMaster('$Country',$CurrVal['country']);
      if (!empty($CurrVal['postCode'])) $tmpAddress[] = $CurrVal['postCode'];

      $CurrVal = implode(" ",$tmpAddress);
    }
  }
}
