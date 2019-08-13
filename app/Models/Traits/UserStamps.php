<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Auth;

trait UserStamps {
  public static function bootUserStamps() {
    static::creating(function($model) {
      if (Auth::guard('api')->check()) $model->created_by = Auth::guard('api')->user()->id;
      else $model->created_by = 'creator';
    });
    static::updating(function($model) {
      if (Auth::check()) $model->updated_by = Auth::user()->id;
      else $model->created_by = 'updator';
    });
    static::deleting(function($model) {
      if (Auth::check()) $model->deleted_by = Auth::user()->id;
      else $model->created_by = 'deletor';
    });
    static::restoring(function($model) {
      $model->deleted_by = null;
    });
  }
}
