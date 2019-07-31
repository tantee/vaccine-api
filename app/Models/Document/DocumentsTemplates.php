<?php

namespace App\Models\Document;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;
use App\Utilities\File;

class DocumentsTemplates extends Model
{
    //
    use SoftDeletes,UserStamps;

    protected $primaryKey = 'templateCode';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public function Documents() {
      return $this->hasMany('\App\Models\Document\Documents','templateCode');
    }

    public static function boot() {
      static::creating(function($model) {
        if (isset($model->printTemplate) && is_array($model->printTemplate) && isset($model->printTemplate['base64string'])) {
          $content = File::base64ToFileContent($model->printTemplate['base64string']);
          $fileExt = File::base64ToFileExtension($model->printTemplate['base64string']);
          $filePath = '/templates/'.$model->templateCode.'.'.$fileExt;

          if (File::overwritePut($filePath,$content)) {
            $model->printTemplate = $filePath;
          } else {
            $model->printTemplate = null;
          }
        }
      });
      static::updating(function($model) {
        if (isset($model->printTemplate) && is_array($model->printTemplate) && isset($model->printTemplate['base64string'])) {
          $content = File::base64ToFileContent($model->printTemplate['base64string']);
          $fileExt = File::base64ToFileExtension($model->printTemplate['base64string']);
          $filePath = '/templates/'.$model->templateCode.'.'.$fileExt;

          if (File::overwritePut($filePath,$content)) {
            $model->printTemplate = $filePath;
          } else {
            $model->printTemplate = null;
          }
        }
      });
  
      parent::boot();
    }
}
