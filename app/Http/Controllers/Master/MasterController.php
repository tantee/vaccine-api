<?php

namespace App\Http\Controllers\Master;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Master\MasterGroupsCollection;

class MasterController extends Controller
{
    public static function getMasterItems(Request $request,$groupKey,$filterText=null) {

      $success = true;
      $errorTexts = [];
      $returnModels = [];

      $masterGroup = \App\Models\Master\MasterGroups::find($groupKey);
      if ($masterGroup != null) {
        if (isset($filterText) && $filterText!="") $returnModels = $masterGroup->items()->where('filterText',$filterText)->get();
        else $returnModels = $masterGroup->items;
      } else {
        $success = false;
        array_push($errorTexts,["errorText"=>"groupKey not founded"]);
      }

      return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function getMasterItemFromValue($groupKey,$itemValue,$English=false) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      $masterGroup = \App\Models\Master\MasterGroups::find($groupKey);
      if ($masterGroup != null) {

        $itemValueField = ($English) ? 'itemValueEN' : 'itemValue';
        $masterItem = $masterGroup->items()->where($itemValueField,$itemValue)->get();

        if ($masterItem->count() == 1) {
          $returnModels = $masterItem;
        } else {
          $success = false;
          array_push($errorTexts,["errorText"=>"Cannot determine ItemCode"]);
        }

      } else {
        $success = false;
        array_push($errorTexts,["errorText"=>"groupKey not founded"]);
      }

      return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function getMasterValue($groupKey,$itemCode) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      $masterItem = \App\Models\Master\MasterItems::where('groupKey',$groupKey)->where('itemCode',$itemCode)->get();

      if ($masterItem->count() == 1) {
        $returnModels = $masterItem->all();
      } else {
        $success = false;
        array_push($errorTexts,["errorText"=>"Cannot find matched master"]);
      }

      return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function translateMaster($groupKey,$itemCode,$English=false) {
      $masterItem = self::getMasterValue($groupKey,$itemCode);
      if ($masterItem["success"]) {
        return ($English) ? $masterItem["returnModels"][0]->itemValueEN : $masterItem["returnModels"][0]->itemValue;
      } else {
        return null;
      }
    }
}
