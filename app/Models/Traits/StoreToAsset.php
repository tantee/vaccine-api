<?php

namespace App\Models\Traits;

use Log;
use App\Http\Controllers\Asset\AssetController;

trait StoreToAsset {
  public static function bootStoreToAsset() {
    static::creating(function($model) {
      $toStores = ($toStores) ? $toStores : [];
      foreach($toStores as $toStore) {
        if (isset($model->$toStore) && \is_array($model->$toStore)) {
          if (isset($model->hn)) \array_walk($model->$toStore,['self','storeToAsset'],$model->hn);
        }
      }
    });
    static::updating(function($model) {
      $toStores = ($toStores) ? $toStores : [];
      foreach($toStores as $toStore) {
        if (isset($model->$toStore) && \is_array($model->$toStore)) {
          if (isset($model->hn)) \array_walk($model->$toStore,['self','storeToAsset'],$model->hn);
        }
      }
    });
  }

  protected static function storeToAsset(&$modelValue,$modelKey,$hn) {
    if (\is_array($modelValue) && isset($modelValue['base64string']) && !isset($modelValue['id'])) {
      if (isset($modelValue['assetType'])) $assetType = $modelValue['assetType'];
      else $assetType = null;

      $result = AssetController::addAssetBase64($hn,$modelValue['base64string'],$assetType);
      if ($result['success']) {
        $modelValue = $result['returnModels'];
      }
    }
  }
}
