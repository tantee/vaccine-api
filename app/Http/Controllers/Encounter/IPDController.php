<?php

namespace App\Http\Controllers\Encounter;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class IPDController extends Controller
{
    public static function autoCharge() {
        Log::info('Begin auto charge');

        $encounters = \App\Models\Registration\Encounters::whereNull('dischargeDateTime')->where('encounterType','IMP')->get();

        foreach($encounters as $encounter) {
            $autoCharge = array_wrap($encounter->clinic->autoCharge);
            foreach($autoCharge as $charge) {
                if (!empty($charge['repeatHour'])) {
                    $existCharge = $encounter->transactions->where('productCode',$charge['productCode'])->where('transactionDateTime','>=',\Carbon\Carbon::now()->subHours($charge['repeatHour']))->exists();
                    if (!$existCharge) {                        
                        if (!empty($charge['limitPerEncounter'])) {
                            $countChargeEncounter = $encounter->transactions->where('productCode',$charge['productCode'])->count();
                            if ($charge['limitPerEncounter'] <= $countChargeEncounter) continue;
                        }
                        if (!empty($charge['limitPerDay'])) {
                            $countChargeDay = \App\Models\Patient\PatientsTransactions::where('hn',$encounter->hn)->whereDate('transactionDateTime',\Carbon\Carbon::now())->count();
                            if ($charge['limitPerDay'] <= $countChargeDay) continue;
                        }
                        Log::info('Auto charge HN '.$encounter->hn.', Encounter '.$encounter->encounterId.', ProductCode '.$charge['productCode']);
                        \App\Http\Controllers\Encounter\TransactionController::addTransactions($encounter->hn,$encounter->encounterId,$charge);
                    }
                }
            }
        }
    }
}
