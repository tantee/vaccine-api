<?php

namespace App\Http\Controllers\Patient;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PatientInsuranceController extends Controller
{
    public static function autoTechnicalDisableNhso() {
        \App\Models\Patient\PatientsInsurances::active()->where('payerType',20)->update(['isTechnicalActive'=>false]);
    }
}
