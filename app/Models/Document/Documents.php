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
      return $this->belongsTo('App\Models\Document\DocumentsTemplates','templateCode')->withTrashed();
    }

    public function Patient() {
      return $this->belongsTo('App\Models\Patient\Patients','hn','hn');
    }

    public function Encounter() {
      return $this->belongsTo('App\Models\Registration\Encounters','encounterId','encounterId')->without(['patient'])->withTrashed();
    }

    public function Mophsent() {
      return $this->hasMany('App\Models\Moph\MophApiSents','documentId','id');
    }

    public function Mophsentsuccess() {
      return $this->hasMany('App\Models\Moph\MophApiSents','documentId','id')->where('isSuccess',true);
    }

    public function getPatientAgeAttribute() {
      if ($this->patient) {
        if ($this->patient->dateOfDeath!==null && $this->created_at->greaterThan($this->patient->dateOfDeath)) $interval = $this->patient->dateOfDeath->diffAsCarbonInterval(\Carbon\Carbon::parse($this->patient->dateOfBirth));
        else $interval = $this->created_at->diffAsCarbonInterval(\Carbon\Carbon::parse($this->patient->dateOfBirth));
      } else {
        $interval = $this->created_at->diffAsCarbonInterval(\Carbon\Carbon::now());
      }
      
      return $interval->locale('th_TH')->forHumans(['parts'=>2]);
    }

    public function getPatientAgeEnAttribute() {
      if ($this->patient) {
        if ($this->patient->dateOfDeath!==null && $this->created_at->greaterThan($this->patient->dateOfDeath)) $interval = $this->patient->dateOfDeath->diffAsCarbonInterval($this->patient->dateOfBirth);
        else $interval = $this->created_at->diffAsCarbonInterval($this->patient->dateOfBirth);
      } else {
        $interval = $this->created_at->diffAsCarbonInterval(\Carbon\Carbon::now());
      }

      return $interval->locale('en')->forHumans(['parts'=>2]);
    }

    public function getIsPdfAttribute() {
      if ($this->isScanned && count($this->data)>0) {
        return self::checkPdf($this->data);
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

    private static function checkPdf($data) {
      if (is_array($data)) {
        foreach($data as $row) {
          if (isset($row['mimeType']) && $row['mimeType']=='application/pdf') return true;
        }
      }
      return false;
    }

    public static function boot() {
        static::updating(function($model) {
            $original = $model->getOriginal();
            //If exist electronic data, move scan data to log
            if (!empty(json_decode($original['data'])) && !$original['isScanned'] && $original['status']=='approved' && $model->isScanned) {
              
              $tmpData = $model->data;
              if (isset($model->hn)) \array_walk($tmpData,['self','storeToAsset'],$model->hn);
              $model->data = $tmpData;

              $oldRevision =  array_wrap($model->revision);
              array_push($oldRevision,[
                "data" => json_encode($model->data),
                "isScanned" => $model->isScanned,
                "isPdf" => ($model->isScanned) ? self::checkPdf($model->data) : false,
                "updated_by" => $model->updated_by,
                "updated_at" => $model->updated_at,
              ]);
              $model->revision = $oldRevision;
              $model->status = $original['status'];

              $model->data = json_decode($original['data'],true);
              $model->isScanned = false;

            } else if ($model->data != json_decode($original['data'],true)) {
                $oldRevision =  array_wrap($model->revision);
                array_push($oldRevision,[
                  "data" => $original['data'],
                  "isScanned" => $original['isScanned'],
                  "isPdf" => ($original['isScanned']) ? self::checkPdf(json_decode($original['data'],true)) : false,
                  "updated_by" => ($original['updated_by']!==null) ? $original['updated_by'] : $original['created_by'],
                  "updated_at" => $original['updated_at'],
                ]);
                $model->revision = $oldRevision;
            }
        });

        static::saved(function($model) {
            if ($model->templateCode=='cv19-vaccine-administration' && $model->status=='approved') {
              \App\Models\Document\Documents::where('templateCode','cv19-vaccine-administration')
                ->where('hn',$model->hn)
                ->whereDate('created_at',$model->created_at)
                ->where('created_at','<',$model->created_at)
                ->where('created_by',$model->created_by)
                ->where('status','approved')
                ->update(['status'=>'review']);
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
