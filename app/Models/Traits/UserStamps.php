<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Auth;

trait UserStamps {
  public static function bootUserStamps() {
    static::creating(function($model) {
      if (Auth::check()) $model->created_by = Auth::user()->id;
      else $model->created_by = '0';
    });
    static::updating(function($model) {
      if (Auth::check()) $model->updated_by = Auth::user()->id;
      else $model->created_by = '0';
    });
    static::deleting(function($model) {
      if (Auth::check()) $model->deleted_by = Auth::user()->id;
      else $model->created_by = '0';
    });
    static::restoring(function($model) {
      $model->deleted_by = null;
    });
  }
}
