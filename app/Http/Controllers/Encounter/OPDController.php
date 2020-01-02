<?php

namespace App\Http\Controllers\Encounter;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OPDController extends Controller
{
    public static function autoCloseEncounter() {
        Log::info('Begin auto close encounter');

        $encounters = \App\Models\Registration\Encounters::whereNull('dischargeDateTime')->where('admitDateTime','<=',\Carbon\Carbon::now()->subHours(48))->where('encounterType','AMB')->get();
        
        foreach($encounters as $encounter) {
            Log::debug('Close encounter '.$encounter->encounterId);
            $discharge = $encounter->transactions()->max('transactionDateTime');
            if ($discharge==null) $discharge = $encounter->admitDateTime;
            
            $encounter->dischargeDateTime = $discharge;
            $encounter->save();
        }
    }
}
