<?php

namespace App\Http\Controllers\Covid19;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RiskController extends Controller
{
    public static function checkRisk($hn,$validPeriod=1) {
        $iconName = "nodata";
        $screeningMini = \App\Models\Document\Documents::where('hn',$hn)->whereIn('templateCode',['cv19-screening-mini','cv19-screening-2021'])
                        ->whereDate('created_at','>=',\Carbon\Carbon::now()->subDays($validPeriod-1))
                        ->where('status','approved')
                        ->orderBy('created_at','desc')
                        ->first();
        $assessmentOneDay = \App\Models\Document\Documents::where('hn',$hn)->whereIn('templateCode',['cv19-assessment','cv19-assessment-2021'])
                        ->whereDate('created_at','>=',\Carbon\Carbon::now()->subDays($validPeriod-1))
                        ->where('status','approved')
                        ->orderBy('created_at','desc')
                        ->first();
        $assessment = \App\Models\Document\Documents::where('hn',$hn)->whereIn('templateCode',['cv19-assessment','cv19-assessment-2021'])
                        ->whereDate('created_at','>=',\Carbon\Carbon::now()->subDays(10))
                        ->where('status','approved')
                        ->orderBy('created_at','desc')
                        ->first();

        if ($screeningMini) {
            if ($screeningMini->data && isset($screeningMini->data["covid19Risk"])) {
                if ($screeningMini->data["covid19Risk"]=="lowrisk") $iconName = "lowrisk";
                else $iconName = "risk";
            }
        }

        if ($assessmentOneDay) {
            if ($assessmentOneDay->data && isset($assessmentOneDay->data["covid19Risk"])) {
                if ($assessmentOneDay->data["covid19Risk"]=="PUI") $iconName = "pui";
                else $iconName = "npui";
            }
        }

        if ($assessment) {
            if ($assessment->data && isset($assessment->data["covid19Risk"])) {
                if ($assessment->data["covid19Risk"]=="PUI") $iconName = "pui";
            }
        }

        return $iconName;
    }
}
