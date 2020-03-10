<?php

namespace App\Document;

define('TBS_CLEANER','\App\Document\clsTbsCleaner');

class clsTbsCleaner
{
  function OnInstall() {
    $this->Version = '1.0.0';
    return array('AfterShow');
  }

  function AfterShow(&$Render) {
      $this->TBS->Source = preg_replace('/\[[^\]]*ifempty=\'(.*)\'.*\]/', '$1', $this->TBS->Source);
      return true;
  }
}