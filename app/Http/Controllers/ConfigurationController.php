<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ConfigurationController extends Controller
{
    private static function getConfiguration($ConfigID,$DefaultValue=null) {
      return $DefaultValue;
    }
}
