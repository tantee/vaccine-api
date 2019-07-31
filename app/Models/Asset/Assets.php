<?php

namespace App\Models\Asset;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;
use App\Http\Controllers\Asset\AssetController;

class Assets extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    public function getBase64dataAttribute() {
      return AssetController::getAssetDataBase64($this);
    }
}
