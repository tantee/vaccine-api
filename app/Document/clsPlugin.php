<?php

namespace App\Document;

use Carbon\Carbon;
use App\Http\Controllers\Master\MasterController;
use Rundiz\Number\NumberThai;
use Rundiz\Number\NumberEng;
use TaNteE\PhpUtilities\ArrayType;

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
      if (!empty($Value)) {
        if (isset($PrmLst['format'])) $format = $PrmLst['format'];
        else $format = "DD MMMM YYYY";

        if (isset($PrmLst['locale'])) $locale = $PrmLst['locale'];
        else $locale = 'th_TH';

        if ($FieldName == "patientData.dateOfBirth" && substr($format, -5) == " YYYY") {
          $byear = Carbon::parse($Value)->timezone(config('app.timezone'))->year + 543;
          $format = \str_replace(" YYYY"," YYYY (".$byear.")",$format);
        } else if ($locale == 'th_TH') {
          $byear = Carbon::parse($Value)->timezone(config('app.timezone'))->year + 543;
          $format = \str_replace("YYYY",$byear,$format);
          $format = \str_replace("YY",substr($byear, -2),$format);
        }

        $Value = Carbon::parse($Value)->timezone(config('app.timezone'))->locale($locale)->isoFormat($format);
      }
    }

    if ($ope == 'formatdatetime') {

      if (isset($PrmLst['format'])) $format = $PrmLst['format'];
      else $format = "DD MMMM YYYY H:mm";

      if (isset($PrmLst['locale'])) $locale = $PrmLst['locale'];
      else $locale = 'th_TH';

      if ($FieldName == "patientData.dateOfBirth" && substr($format, -5) == " YYYY") {
        $byear = Carbon::parse($Value)->timezone(config('app.timezone'))->year + 543;
        $format = \str_replace(" YYYY"," YYYY (".$byear.")",$format);
      } else if ($locale = 'th_TH') {
        $byear = Carbon::parse($Value)->timezone(config('app.timezone'))->year + 543;
        $format = \str_replace("YYYY",$byear,$format);
        $format = \str_replace("YY",substr($byear, -2),$format);
      }

      $Value = Carbon::parse($Value)->timezone(config('app.timezone'))->locale($locale)->isoFormat($format);
    }
    
    if ($ope == 'formatname') {
      if (\is_array($Value)) {
        $forcedFullname = (isset($PrmLst['full'])) ? true : false;

        $tmpName = [];

        if (!empty($Value['nameType']) && ($Value['nameType']=='EN' || $Value['nameType']=='ALIAS_EN' )) $English = true;
        else $English = false;

        if (!empty($Value['namePrefix'])) $tmpName[] = MasterController::translateMaster('$NamePrefix',$Value['namePrefix'],$English);
        if (!empty($Value['firstName'])) $tmpName[] = $Value['firstName'];
        if (!empty($Value['middleName'])) $tmpName[] = ($forcedFullname) ? $Value['middleName'] :  iconv_substr(mb_ereg_replace("???|???|???|???|???","",$Value['middleName']),0,1).'.';
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
      $Value = number_format(floatval($Value),2);
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

    if ($ope == 'formatdoctor') {
      if (isset($PrmLst['lang']) && $PrmLst['lang']=="en") $English=true;
      else $English=false;

      if (isset($PrmLst['withCode'])) $withCode=true;
      else $withCode=false;

      $doctor = \App\Models\Master\Doctors::find($Value);
      if ($doctor !== null) {
        $Value = ($English) ? $doctor->nameEN : $doctor->nameTH;
        if ($withCode) $Value = $Value.' '.(($English) ? 'License No. ' : '??????????????????????????????????????????????????????????????? ').$doctor->licenseNo;
      }
    }

    if ($ope == 'formatmodel') {
      if (isset($PrmLst['model']) && isset($PrmLst['valueField'])) {
        $modelName = $PrmLst['model'];
        try {
          $model = $modelName::find($Value);
          if ($model && $model->{$PrmLst['valueField']}) {
            $Value = $model->{$PrmLst['valueField']};
          }
        } catch (\Exception $e) {
        }
      }
    }

    if ($ope == 'formatuser') {
      $user = \App\Models\User\Users::where('username',$Value)->first();
      if ($user !== null) {
        $Value = $user->name;
      }
    }

    if ($ope == 'checkbox') {
      if (isset($PrmLst['inverse'])) $Value=!$Value;
      $Value = ($Value) ? '???' : '???';
    }

    if ($ope == 'code128') {
      if (!empty($Value)) {
        $Value = Code128Encoder::encode($Value);
      }
    }
  }
}
