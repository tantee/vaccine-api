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
      return $this->belongsTo('App\Models\Registration\Encounters','encounterId','encounterId')->without(['patient'])->withTrashed();
    }

    public function getPatientAgeAttribute() {
      if ($this->patient->dateOfDeath!==null && $this->created_at->greaterThan($this->patient->dateOfDeath)) $interval = $this->patient->dateOfDeath->diffAsCarbonInterval($this->patient->dateOfBirth);
      else $interval = $this->created_at->diffAsCarbonInterval($this->patient->dateOfBirth);

      return $interval->locale('th_TH')->forHumans(['parts'=>2]);
    }

    public function getPatientAgeEnAttribute() {
      if ($this->patient->dateOfDeath!==null && $this->created_at->greaterThan($this->patient->dateOfDeath)) $interval = $this->patient->dateOfDeath->diffAsCarbonInterval($this->patient->dateOfBirth);
      else $interval = $this->created_at->diffAsCarbonInterval($this->patient->dateOfBirth);

      return $interval->locale('en')->forHumans(['parts'=>2]);
    }

    public function getIsPdfAttribute() {
      if ($this->isScanned && count($this->data)>0) {
        foreach($this->data as $row) {
          if (isset($row['mimeType']) && $row['mimeType']=='application/pdf') return true;
        }
        return false;
      } else {
        return false;
      }
    }

    public function toArray()
    {
        $toArray = parent::toArray();

        $toArray['isPdf'] = $this->is_pdf;

        return $toArray;
    }

    public static function boot() {
        static::updating(function($model) {
            $original = $model->getOriginal();
            $newData = $model->data;
            if ($newData != $original['data']) {
                $oldRevision =  array_wrap($model->revision);
                array_push($oldRevision,[
                  "data" => $original['data'],
                  "isScanned" => $original['isScanned'],
                  "updated_by" => ($original['updated_by']!==null) ? $original['updated_by'] : $original['created_by'],
                  "updated_at" => $original['updated_at'],
                ]);
                $model->revision = $oldRevision;
            }
        });

        parent::boot();
    }

    protected $casts = [
      'data' => 'array',
      'revision' => 'array',
    ];

    protected $toStores = ['data'];

    protected $appends = ['patient_age'];
}
