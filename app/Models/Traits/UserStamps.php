<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Auth;

trait UserStamps {
  public static function bootUserStamps() {
    static::creating(function($model) {
      if (Auth::guard('api')->check()) $model->created_by = Auth::guard('api')->user()->username;
      else $model->created_by = 'anonymous';
    });
    static::updating(function($model) {
      $original = $model->getOriginal();
      if (array_key_exists('deleted_by',$original) && $model->deleted_by == $original['deleted_by']) {
        if (Auth::guard('api')->check()) $model->updated_by = Auth::guard('api')->user()->username;
        else $model->updated_by = 'anonymous';
      } else {
        $model->timestamps = false;
      }
    });
    static::deleting(function($model) {
      if (Auth::guard('api')->check()) $model->deleted_by = Auth::guard('api')->user()->username;
      else $model->deleted_by = 'anonymous';
      $model->save();
    });
    static::restoring(function($model) {
      $model->deleted_by = null;
    });
  }
}
