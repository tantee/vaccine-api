<?php

namespace App\Document;

define('TBS_MASTERITEM','\App\Document\clsMasterItem');

class clsMasterItem
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
    if (substr($ope,0,3) == 'mst') {
      $groupKey = substr($ope,3,strlen($ope)-3);
      if (isset($PrmLst['lang']) && $PrmLst['lang']=="en") $English = true;
      else $English = false;
      $Value = \App\Http\Controllers\Master\MasterController::translateMaster($groupKey,$Value,$English);
    }
  }
}
