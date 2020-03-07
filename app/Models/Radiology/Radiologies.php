<?php

namespace App\Models\Radiology;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class Radiologies extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    public function scopeUnreported($query) {
      return $query->doesntHave('reportDocument')->orWhereHas('reportDocument',function($q) {
        $q->where('status','<>','approved');
      });
    }

    public function scopeReported($query) {
      return $query->whereHas('reportDocument',function($q) {
        $q->where('status','approved');
      });
    }

    public function Patient() {
      return $this->belongsTo('App\Models\Patient\Patients','hn','hn');
    }

    public function reportDocument() {
      return $this->hasOne('App\Models\Document\Documents','id','reportDocumentId');
    }

    public function requestDocument() {
      return $this->hasOne('App\Models\Document\Documents','id','requestDocumentId');
    }

    public function reportingDoctor() {
        return $this->hasOne('App\Models\Master\Doctors','doctorCode','reportingDoctorCode');
    }

    public function toArray()
    {
        $toArray = parent::toArray();

        $toArray['reportDocument'] = $this->report_document;
        $toArray['requestDocument'] = $this->request_document;
        $toArray['reportingDoctor'] = $this->reporting_doctor;

        return $toArray;
    }

    protected $with = ['reporting_doctor'];
}
