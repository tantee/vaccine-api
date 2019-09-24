<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class PatientsTransactions extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    public function scopeUninvoiced($query) {
      return $query->whereNull('invoiceId');
    }

    public function Product() {
        return $this->hasOne('App\Models\Master\Products','productCode','productCode');
    }

    public function Encounter() {
        return $this->hasOne('App\Models\Registration\Encounters','encounterId','encounterId');
    }

    public function getOrderLocationAttribute() {
        return \App\Models\Master\Locations::find($this->order_location_code);
    }

    public function getOrderClinicAttribute() {
        return \App\Models\Master\Clinics::find($this->order_clinic_code);
    }

    public function getOrderDoctorAttribute() {
        return \App\Models\Master\Doctors::find($this->order_doctor_code);
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

        if (!$this->isForceSelfPay) {
            if ($this->soldPatientsInsurancesId != null) return \App\Models\Patient\PatientsInsurances::find($this->soldPatientsInsurancesId);

            $Insurances = \App\Models\Patient\PatientsInsurances::where('hn',$this->hn)->activeAt($this->transactionDateTime)->get();

            foreach($Insurances as $PatientInsurance) {
                $Insurance = $PatientInsurance->Condition;
                if ($this->Encounter->encounterType == "IMP" && !$Insurance->isApplyToIpd) break;
                if ($this->Encounter->encounterType != "IMP" && !$Insurance->isApplyToOpd) break;

                if ($Insurance->isCoverageAll) {
                    $returnInsurance = $PatientInsurance;
                    foreach(collect($Insurance->conditions)->sortBy('conditionPriority') as $condition) {
                        if ($condition['coverage']=="allow") {
                            if (($condition['conditionType']=="categoryInsurance" && $condition['conditionCode']==$this->categoryInsurance) ||
                                ($condition['conditionType']=="categoryCgd" && $condition['conditionCode']==$this->categoryCgd) ||
                                ($condition['conditionType']=="productCode" && $condition['conditionCode']==$this->productCode)) break;
                        } else {
                            if (($condition['conditionType']=="categoryInsurance" && $condition['conditionCode']==$this->categoryInsurance) ||
                                ($condition['conditionType']=="categoryCgd" && $condition['conditionCode']==$this->categoryCgd) ||
                                ($condition['conditionType']=="productCode" && $condition['conditionCode']==$this->productCode)) {
                                    $returnInsurance = null;
                                    break;
                                }
                        }
                    }
                } else {
                    foreach(collect($Insurance->conditions)->sortBy('conditionPriority') as $condition) {
                        if ($condition['coverage']=="allow") {
                            if (($condition['conditionType']=="categoryInsurance" && $condition['conditionCode']==$this->categoryInsurance) ||
                                ($condition['conditionType']=="categoryCgd" && $condition['conditionCode']==$this->categoryCgd) ||
                                ($condition['conditionType']=="productCode" && $condition['conditionCode']==$this->productCode)) {
                                    $returnInsurance = $PatientInsurance;
                                    break;
                                }
                        } else {
                            if (($condition['conditionType']=="categoryInsurance" && $condition['conditionCode']==$this->categoryInsurance) ||
                                ($condition['conditionType']=="categoryCgd" && $condition['conditionCode']==$this->categoryCgd) ||
                                ($condition['conditionType']=="productCode" && $condition['conditionCode']==$this->productCode)) break;
                        }
                    }
                }
                if ($returnInsurance != null) break;
            }
        }

        return $returnInsurance;
    }

    public function getPriceAttribute() {
        if ($this->soldPrice != null) return $this->soldPrice;
        $insurance = $this->Insurance;
        if ($insurance == null) return $this->Product->price1;
        else {
            $price = 'price'.$insurance->Condition->priceLevel;
            return ($this->Product->$price!=null) ? $this->Product->$price : $this->Product->price1;
        }
    }

    public function getDiscountAttribute() {
        if ($this->soldDiscount != null) return $this->soldDiscount;
        $insurance = $this->Insurance;
        if ($insurance == null) return 0;
        else return $insurance->Condition->discount;
    }

    public function getTotalDiscountAttribute() {
        if ($this->soldTotalDiscount != null) return $this->soldTotalDiscount;
        return round(($this->price*$this->quantity*$this->discount/100),2);
    }

    public function getTotalPriceAttribute() {
        if ($this->soldTotalPrice != null) return $this->soldTotalPrice;
        return round($this->price*$this->quantity,2);
    }

    public function getFinalPriceAttribute() {
        if ($this->soldFinalPrice != null) return $this->soldFinalPrice;
        return round(($this->price*$this->quantity)-($this->price*$this->quantity*$this->discount/100),2);
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

    public function toArray()
    {
        $toArray = parent::toArray();
        $toArray['categoryInsurance'] = $this->category_insurance;
        $toArray['categoryCgd'] = $this->category_cgd;
        $toArray['orderDoctor'] = $this->order_doctor;
        $toArray['orderDoctorCode'] = $this->order_doctor_code;
        $toArray['orderClinic'] = $this->order_clinic;
        $toArray['orderClinicCode'] = $this->order_clinic_code;
        $toArray['orderLocation'] = $this->order_location;
        $toArray['orderLocationCode'] = $this->order_location_code;

        $toArray['totalDiscount'] = $this->total_discount;
        $toArray['totalPrice'] = $this->total_price;
        $toArray['finalPrice'] = $this->final_price;
        
        return $toArray;
    }

    protected $dates = [
        'transactionDateTime',
    ];

    protected $casts = [
        "soldPrice" => "float",
        "soldDiscount" => "float",
        "soldTotalPrice" => "float",
        "soldTotalDiscount" => "float",
        "soldFinalPrice" => "float",
    ];

    protected $with = ['Product'];

    protected $appends = ['insurance','discount','price'];
}
