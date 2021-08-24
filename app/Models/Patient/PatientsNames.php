<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use TaNteE\LaravelModelApi\Traits\UserStamps;
use App\Http\Controllers\Master\MasterController;

class PatientsNames extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    public function Patient() {
      return $this->belongsTo('App\Models\Patient\Patients','hn','hn');
    }

    public function getFullnameAttribute() {
        $tmpName = [];

        if (!empty($this->nameType) && ($this->nameType=='EN' || $this->nameType=='ALIAS_EN' )) $English = true;
        else $English = false;

        if (!empty($this->namePrefix)) $tmpName[] = MasterController::translateMaster('$NamePrefix',$this->namePrefix,$English);
        if (!empty($this->firstName)) $tmpName[] = $this->firstName;
        if (!empty($this->middleName)) $tmpName[] = $this->middleName;
        if (!empty($this->lastName)) $tmpName[] = $this->lastName;
        if (!empty($this->nameSuffix)) $tmpName[] = MasterController::translateMaster('$NameSuffix',$this->nameSuffix,$English);

        return implode(" ",$tmpName);
    }

    public function getNamePrefixAttribute($value) {
        $returnValue = $value;

        if ($value=="001" && $this->patient) {
            if ($this->patient->dateOfBirth->isBefore(\Carbon\Carbon::now()->subYears(15)->startOfDay())) {
                $returnValue = "003";
            }
        }

        if ($value=="002" && $this->patient) {
            if ($this->patient->dateOfBirth->isBefore(\Carbon\Carbon::now()->subYears(15)->startOfDay())) {
                $returnValue = "004";
            }
        }

        return $returnValue;
    }
}
