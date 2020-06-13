<?php

namespace App\Http\Controllers\Import;

use Log;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PacsImportController extends Controller
{
    //
    public static function Import() {
        Log::info('Begin importing PACS data');

        $qidoUri = env('PACS_QIDO_URI','https://pacs.canceralliance.co.th/dcm4chee-arc/aets/CAHPACS/rs');
        $qidoUri = implode('/',[$qidoUri,'studies']);

        $hns = \App\Models\Registration\Encounters::whereNull('dischargeDateTime')->select('hn')->distinct()->without('Patient')->get()->pluck('hn');
        foreach($hns as $hn) {
            Log::debug('importing PACS for '.$hn);
            $query['PatientID'] = $hn;
            $query['includefield'] = '00081030,00080060';
            $requestData['query'] = $query;

            $client = new \GuzzleHttp\Client();
            try {
                $res = $client->request("GET",$qidoUri,$requestData);
                $pacsDatas = json_decode((String)$res->getBody(),true);
                foreach(array_wrap($pacsDatas) as $pacsData) {
                    $studyDateTime = $pacsData['00080020']['Value'][0].' '.\substr($pacsData['00080030']['Value'][0],0,2).':'.\substr($pacsData['00080030']['Value'][0],2,2).':'.\substr($pacsData['00080030']['Value'][0],4,2);
                    $studyDateTime = \Carbon\Carbon::parse($studyDateTime);

                    if (isset($pacsData['00800050']) && isset($pacsData['00800050']['Value']) && isset($pacsData['00800050']['Value'][0]) && !empty($pacsData['00800050']['Value'][0])) $radiology = \App\Models\Radiology\Radiologies::firstOrNew(["accessionNumber"=>$pacsData['00800050']['Value'][0]]);
                    else $radiology = \App\Models\Radiology\Radiologies::firstOrNew(["hn"=>$hn,"studyDateTime"=>$studyDateTime,"modality"=>$pacsData['00080061']['Value'][0]]);

                    $radiology["hn"] = $hn;
                    $radiology["accessionNumber"] = isset($pacsData['00800050']['Value']) ? $pacsData['00080050']['Value'][0] : null;
                    $radiology["modality"] = (isset($pacsData['00080061']['Value'])) ? $pacsData['00080061']['Value'][0] : null;
                    $radiology["description"] = (isset($pacsData['00081030']['Value'])) ? $pacsData['00081030']['Value'][0] : null;
                    $radiology["studyDateTime"] = $studyDateTime;
                    $radiology["uid"] = $pacsData['0020000D']['Value'][0];
                    $radiology["referringDoctor"] = (isset($pacsData['00080090']['Value'])) ? self::parsePN($pacsData['00080090']['Value'][0]['Alphabetic']) : null;
                    $radiology["imageCount"] = $pacsData['00201208']['Value'][0];

                    if ($radiology->isDirty()) $radiology->save();
                }
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }
        }
    }

    private static function parsePN($pn) {
        $pn = explode('^',$pn);
        if (count($pn)==1) return $pn[0];
        if (count($pn)==2) return $pn[1].self::emptyOrLead($pn[0]);
        if (count($pn)==3) return $pn[1].self::emptyOrLead($pn[2]).self::emptyOrLead($pn[0]);
        if (count($pn)==4) return $pn[3].self::emptyOrLead($pn[1]).self::emptyOrLead($pn[2]).self::emptyOrLead($pn[0]);
        if (count($pn)==5) return $pn[3].self::emptyOrLead($pn[1]).self::emptyOrLead($pn[2]).self::emptyOrLead($pn[0]).self::emptyOrLead($pn[4],',');
    }
    
    private static function emptyOrLead($str,$lead=' ') {
        return (empty($str)) ? '' : $lead.$str;
    }
}
