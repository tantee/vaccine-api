<?php

namespace App\Models\Radiology;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class Radiologies extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    public function Patient() {
      return $this->belongsTo('App\Models\Patient\Patients','hn','hn');
    }

    public function Report() {
      return $this->hasOne('App\Models\Document\Documents','id','reportDocumentId');
    }

    public function Request() {
      return $this->hasOne('App\Models\Document\Documents','id','requestDocumentId');
    }

    public function Doctor() {
        return $this->hasOne('App\Models\Master\Doctors','doctorCode','reportingDoctor');
    }

    protected $with = ['Doctor'];
}
