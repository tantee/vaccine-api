<?php

namespace App\Document;

use Carbon\Carbon;
use App\Http\Controllers\Master\MasterController;

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

      $Value = Carbon::parse($Value)->locale($locale)->isoFormat($format);
    }
    if ($ope == 'formatname') {
      if (\is_array($Value)) {
        $tmpName = [];

        if (!empty($Value['nameType']) && ($Value['nameType']=='EN' || $Value['nameType']=='ALIAS_EN' )) $English = true;
        else $English = false;

        if (!empty($Value['namePrefix'])) $tmpName[] = MasterController::translateMaster('$NamePrefix',$Value['namePrefix']);
        if (!empty($Value['firstName'])) $tmpName[] = $Value['firstName'];
        if (!empty($Value['middleName'])) $tmpName[] = $Value['middleName'];
        if (!empty($Value['lastName'])) $tmpName[] = $Value['lastName'];
        if (!empty($Value['nameSuffix'])) $tmpName[] = MasterController::translateMaster('$NameSuffix',$Value['nameSuffix']);

        $Value = implode(" ",$tmpName);
      }
    }
    if ($ope == 'formatcurr') {
      $Value = number_format($Value,2);
    }
  }
}
