<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use TaNteE\LaravelModelApi\Traits\UserStamps;

class PatientsTrackers extends Model
{
    use HasFactory,SoftDeletes,UserStamps;

    protected $guarded = [];

    public function scopeNoRecord($query) {
        return $query->where('module','covidvaccine.administration')
                ->whereDate('created_at',\Carbon\Carbon::now())
                ->whereNotIn('hn',function($query) {
                    $query->select('hn')->distinct()
                        ->from('documents')
                        ->whereDate('created_at',\Carbon\Carbon::now())
                        ->where('templateCode','cv19-vaccine-administration')
                        ->where('status','approved');
                });
    }

    public function Patient() {
        return $this->belongsTo('App\Models\Patient\Patients','hn','hn');
    }
}
