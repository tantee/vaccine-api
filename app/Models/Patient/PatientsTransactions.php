<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Model;

class PatientsTransactions extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    public function Product() {
        return $this->hasOne('App\Models\Master\Products','productCode','productCode');
    }

    public function Encounter() {
        return $this->hasOne('App\Models\Registration\Encounters','encounterId','encounterId');
    }

    public function Insurances() {
        return $this->hasMany('App\Models\Patient\PatientsInsurances','hn','hn')->activeAt($this->transactionDateTime)->orderBy('priority','asc');
    }

    public function getOrderLocationAttribute() {
        return \App\Models\Master\Locations::find($this->orderLocationCode);
    }

    public function getOrderClinicAttribute() {
        return \App\Models\Master\Clinics::find($this->orderClinicCode);
    }

    public function getOrderDoctorAttribute() {
        return \App\Models\Master\Doctors::find($this->orderDoctorCode);
    }

    public function performLocation() {
        return $this->hasOne('App\Models\Master\Locations','locationCode','performLocationCode');
    }

    public function performClinic() {
        return $this->hasOne('App\Models\Master\Clinics','clinicCode','performClinicCode');
    }

    public function performDoctor() {
        return $this->hasOne('App\Models\Master\Doctors','doctorCode','performDoctorCode');
    }

    public function getInsuranceAttribute() {
        $returnInsurance = null;
        foreach($this->Insurances as $PatientInsurance) {
            $Insurance = $PatientInsurance->Insurance;
            if ($this->Encounter->encounterType == "IMP" && !$Insurance->isApplyToIpd) break;
            if ($this->Encounter->encounterType != "IMP" && !$Insurance->isApplyToOpd) break;

            if ($Insurance->isCoverageAll) {
                $returnInsurance = $PatientInsurance;
                foreach(collect($Insurance->conditions)->sortBy('conditionPriority') as $condition) {
                    if ($condition['coverage']=="allow") {
                        if (($condition['conditionType']=="productCategoryInsurance" && $condition['conditionCode']==$this->categoryInsurance) ||
                            ($condition['conditionType']=="productCategoryCgd" && $condition['conditionCode']==$this->categoryCgd) ||
                            ($condition['conditionType']=="product" && $condition['conditionCode']==$this->productCode)) break;
                    } else {
                        if (($condition['conditionType']=="productCategoryInsurance" && $condition['conditionCode']==$this->categoryInsurance) ||
                            ($condition['conditionType']=="productCategoryCgd" && $condition['conditionCode']==$this->categoryCgd) ||
                            ($condition['conditionType']=="product" && $condition['conditionCode']==$this->productCode)) {
                                $returnInsurance = null;
                                break;
                            }
                    }
                }
            } else {
                foreach(collect($Insurance->conditions)->sortBy('conditionPriority') as $condition) {
                    if ($condition['coverage']=="allow") {
                        if (($condition['conditionType']=="productCategoryInsurance" && $condition['conditionCode']==$this->categoryInsurance) ||
                            ($condition['conditionType']=="productCategoryCgd" && $condition['conditionCode']==$this->categoryCgd) ||
                            ($condition['conditionType']=="product" && $condition['conditionCode']==$this->productCode)) {
                                $returnInsurance = $PatientInsurance;
                                break;
                            }
                    } else {
                        if (($condition['conditionType']=="productCategoryInsurance" && $condition['conditionCode']==$this->categoryInsurance) ||
                            ($condition['conditionType']=="productCategoryCgd" && $condition['conditionCode']==$this->categoryCgd) ||
                            ($condition['conditionType']=="product" && $condition['conditionCode']==$this->productCode)) break;
                    }
                }
            }
            if ($returnInsurance != null) break;
        }
        return $returnInsurance;
    }

    public function getPriceAttribute() {
        $insurance = $this->Insurance;
        if ($insurance == null) return $this->Product->price1;
        else {
            $price = 'price'.$insurance->priceLevel;
            return $this->Product->$price;
        }
    }

    public function getDiscountAttribute() {
        $insurance = $this->Insurance;
        if ($insurance == null) return 0;
        else return $this->Product->$discount;
    }

    public function getCategoryInsuranceAttribute($value) {
        if ($value != null) return $value;
        else return $this->product->categoryInsurance;
    }

    public function getCategoryCgdAttribute($value) {
        if ($value != null) return $value;
        else return $this->Product->categoryCgd;
    }

    public function getOrderDoctorCodeAttribute($value) {
        if ($value != null) return $value;
        else return $this->Encounter->doctorCode;
    }

    public function getOrderClinicCodeAttribute($value) {
        if ($value != null) return $value;
        else return $this->Encounter->clinicCode;
    }

    public function getOrderLocationCodeAttribute($value) {
        if ($value != null) return $value;
        else return $this->Encounter->locationCode;
    }

    protected $dates = [
        'transactionDateTime',
    ];

    protected $with = ['Product','Encounter','Insurances'];

    protected $appends = ['insurance','discount','price'];
}
