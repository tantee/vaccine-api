<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Auth;

trait UserStamps {
  public static function bootUserStamps() {
    static::creating(function($model) {
      if (Auth::guard('api')->check()) $model->created_by = Auth::guard('api')->user()->username;
      else $model->created_by = '0';
    });
    static::updating(function($model) {
      if (Auth::guard('api')->check()) $model->updated_by = Auth::guard('api')->user()->username;
      else $model->updated_by = '0';
    });
    static::deleting(function($model) {
      if (Auth::guard('api')->check()) $model->deleted_by = Auth::guard('api')->user()->username;
      else $model->deleted_by = '0';
      $model->save();
    });
    static::restoring(function($model) {
      $model->deleted_by = null;
    });
  }
}
