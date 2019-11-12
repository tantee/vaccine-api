<?php

namespace App\Models\Document;

use Watson\Rememberable\Rememberable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;
use App\Utilities\File;

class DocumentsTemplates extends Model
{
    //
    use SoftDeletes,UserStamps,Rememberable;

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
          $filePath = '/templates/'.$model->templateCode.'.'.$model->revisionId.'.'.$fileExt;

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
          $filePath = '/templates/'.$model->templateCode.'.'.$model->revisionId.'.'.$fileExt;

          if (File::overwritePut($filePath,$content)) {
            $model->printTemplate = $filePath;
          } else {
            $model->printTemplate = null;
          }
        }
      });

      static::saved(function($model) {
          $model::flushCache();
      });

      static::deleted(function($model) {
          $model::flushCache();
      });

      static::restored(function($model) {
          $model::flushCache();
      });
  
      parent::boot();
    }
    
    protected $dates = [
        'revisionDate',
    ];

    protected $rememberFor = 60;
    protected $rememberCacheTag = 'documentstemplates_query';
}
