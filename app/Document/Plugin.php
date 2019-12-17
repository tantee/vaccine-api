<?php

namespace App\Document;

use Carbon\Carbon;
use App\Http\Controllers\Master\MasterController;
use Rundiz\Number\NumberThai;
use Rundiz\Number\NumberEng;
use App\Utilities\ArrayType;

define('TBS_CAHPLUGIN','\App\Document\clsPlugin');

class clsPlugin
{
  function OnInstall() {
    $this->Version = '1.0.0';
    return array('OnOperation','OnCommand');
  }

  function OnCommand() {
    return true;
  }

  function OnOperation($FieldName,&$Value,&$PrmLst,&$Txt,$PosBeg,$PosEnd,&$Loc) {
    $ope = $PrmLst['ope'];
    if ($ope == 'formatdate') {

      if (isset($PrmLst['format'])) $format = $PrmLst['format'];
      else $format = "DD MMMM YYYY";

      if (isset($PrmLst['locale'])) $locale = $PrmLst['locale'];
      else $locale = 'th_TH';

      if ($FieldName == "patientData.dateOfbirth" && substr($format, -5) == " YYYY") {
        $byear = Carbon::parse($Value)->year + 543;
        $format = \str_replace(" YYYY"," YYYY (".$byear.")",$format);
      }

      $Value = Carbon::parse($Value)->locale($locale)->isoFormat($format);
    }
    if ($ope == 'formatname') {
      if (\is_array($Value)) {
        $tmpName = [];

        if (!empty($Value['nameType']) && ($Value['nameType']=='EN' || $Value['nameType']=='ALIAS_EN' )) $English = true;
        else $English = false;

        if (!empty($Value['namePrefix'])) $tmpName[] = MasterController::translateMaster('$NamePrefix',$Value['namePrefix'],$English);
        if (!empty($Value['firstName'])) $tmpName[] = $Value['firstName'];
        if (!empty($Value['middleName'])) $tmpName[] = $Value['middleName'];
        if (!empty($Value['lastName'])) $tmpName[] = $Value['lastName'];
        if (!empty($Value['nameSuffix'])) $tmpName[] = MasterController::translateMaster('$NameSuffix',$Value['nameSuffix'],$English);

        $Value = implode(" ",$tmpName);
      }
    }
    if ($ope == "formataddress") {
      if (\is_array($Value)) {
        $tmpAddress = [];

        $isThai = $Value['country'] == "TH";

        if (!empty($Value['address'])) $tmpAddress[] = $Value['address'];
        if (!empty($Value['village'])) $tmpAddress[] = $Value['village'];
        if (!empty($Value['moo'])) $tmpAddress[] = $Value['moo'];
        if (!empty($Value['trok'])) $tmpAddress[] = $Value['trok'];
        if (!empty($Value['soi'])) $tmpAddress[] = $Value['soi'];
        if (!empty($Value['street'])) $tmpAddress[] = $Value['street'];
        if (!empty($Value['subdistrict'])) $tmpAddress[] = ($isThai) ? MasterController::translateMaster('$Subdistrict',$Value['subdistrict']) : $Value['subdistrict'];
        if (!empty($Value['district'])) $tmpAddress[] = ($isThai) ? MasterController::translateMaster('$District',$Value['district']) : $Value['district'];;
        if (!empty($Value['province'])) $tmpAddress[] = ($isThai) ? MasterController::translateMaster('$Province',$Value['province']) : $Value['province'];
        if (!empty($Value['country'])) $tmpAddress[] = MasterController::translateMaster('$Country',$Value['country']);
        if (!empty($Value['postCode'])) $tmpAddress[] = $Value['postCode'];

        $Value = implode(" ",$tmpAddress);
      }
    }
    if ($ope == 'formatcurr') {
      $Value = number_format($Value,2);
    }
    if ($ope == 'formatinsurance') {
      if (isset($PrmLst['full'])) $full = (boolean)$PrmLst['full'];
      else $full = false;

      if (isset($PrmLst['lang']) && $PrmLst['lang']=="en") $English=true;
      else $English=false;

      if (is_array($Value)) {
        if (count($Value)>0) {
          if (ArrayType::isAssociative($Value))$Value = [$Value];

          $tmpInsuranceNames = [];
          foreach($Value as $insurance) {
            if (isset($insurance["payerType"])) {
              $tmpInsuranceName = \App\Http\Controllers\Master\MasterController::translateMaster('$PayerType',$insurance["payerType"],$English);
              if ($insurance["payer"] !== null && $full) {
                $tmpInsuranceName .= " (".$insurance["payer"]["payerName"].")";
              }
              $tmpInsuranceNames[] = $tmpInsuranceName;
            } else if (isset($insurance["condition"])) {
              $tmpInsuranceNames[] = $insurance["condition"]["insuranceName"];
            }
          }

          $Value = implode(",",array_unique($tmpInsuranceNames));
        } else {
          $Value = ($English) ? "Cash" : "เงินสด";
        }
      }
    }
    if ($ope == 'currtext') {
      if (isset($PrmLst['lang']) && $PrmLst['lang']=="en") {
        $convert = new NumberEng();
        $Value = $convert->convertNumber((float)$Value);
      } else {
        $convert = new NumberThai();
        $Value = $convert->convertBaht((float)$Value);
      }
    }
  }
}
