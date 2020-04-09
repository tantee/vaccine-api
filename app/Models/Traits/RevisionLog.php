<?php

namespace App\Models\Traits;

use App\Http\Controllers\Asset\AssetController;

trait RevisionLog {
  public static function bootRevisionLog() {
    static::updating(function($model) {
      $model->saveRevision();
    });
  }

  public function saveRevision() {
    if (isset($this->toRevisionField) && is_array($this->toRevisionField) && isset($this->revisionField)) {
      $original = $model->getOriginal();
      $isDataChange = false;
      $newRevision = [];
      foreach($this->toRevisionField as $field) {
        if ($this->$field != $original[$field]) {
          $isDataChange = true;
          $newRevision[$field] = $original[$field];
        }
      }
      if ($isDataChange) {
        $oldRevision =  array_wrap($model->${$this->revisionField});
        $newRevision['updated_by'] = ($original['updated_by']!==null) ? $original['updated_by'] : $original['created_by'];
        $newRevision['updated_at'] = $original['updated_at'];
        array_push($oldRevision,$newRevision);
        $model->${$this->revisionField} = $oldRevision;
      }
    }
  }
}
