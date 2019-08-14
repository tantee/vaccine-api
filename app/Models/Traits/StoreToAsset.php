<?php

namespace App\Models\Traits;

use Log;
use App\Http\Controllers\Asset\AssetController;

trait StoreToAsset {
  public static function bootStoreToAsset() {
    static::creating(function($model) {
      $model->storeWhenSave();
    });
    static::updating(function($model) {
      $model->storeWhenSave();
    });
  }

  public function storeWhenSave() {
    $toStores = (isset($this->toStores)) ? $this->toStores : [];
    foreach($toStores as $toStore) {
      if (isset($this->$toStore) && \is_array($this->$toStore)) {
        log::debug('array_walk fired');
        $tmpFieldToStore = $this->$toStore;
        if (isset($this->hn)) \array_walk($tmpFieldToStore,['self','storeToAsset'],$this->hn);
        $this->$toStore = $tmpFieldToStore;
      }
    }
  }

  protected static function storeToAsset(&$modelValue,$modelKey,$hn) {
    log::debug('storetoasset fired');
    if (\is_array($modelValue) && isset($modelValue['base64string']) && !isset($modelValue['id'])) {
      if (isset($modelValue['assetType'])) $assetType = $modelValue['assetType'];
      else $assetType = null;
      log::debug('base64string fired');
      $result = AssetController::addAssetBase64($hn,$modelValue['base64string'],$assetType);
      if ($result['success']) {
        $modelValue = $result['returnModels'];
      }
    }
  }
}
