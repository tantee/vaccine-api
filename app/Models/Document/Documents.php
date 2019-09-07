<?php

namespace App\Models\Document;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;
use App\Models\Traits\StoreToAsset;

class Documents extends Model
{
    //
    use SoftDeletes,UserStamps,StoreToAsset;

    protected $guarded = [];

    public function Template() {
      return $this->belongsTo('App\Models\Document\DocumentsTemplates','templateCode');
    }

    public function Patient() {
      return $this->belongsTo('App\Models\Patient\Patients','hn','hn');
    }

    public function Encounter() {
      return $this->belongsTo('App\Models\Registration\Encounters','encounterId','encounterId');
    }

    public function getPatientAgeAttribute() {
      if ($this->patient->dateOfDeath!==null && $this->created_at->greaterThan($this->patient->dateOfDeath)) $interval = $this->patient->dateOfDeath->diffAsCarbonInterval($this->patient->dateOfBirth);
      else $interval = $this->created_at->diffAsCarbonInterval($this->patient->dateOfBirth);
      
      $interval->setLocale('th_TH');

      return $interval->forHumans(['parts'=>2]);
    }

    protected $casts = [
      'data' => 'array',
      'revision' => 'array',
    ];

    protected $dates = [
        'revisionDate',
    ];

    protected $toStores = ['data'];

    protected $appends = ['patient_age'];
}
