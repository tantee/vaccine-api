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

                    if (!empty($pacsData['00800050']['Value'][0])) $radiology = \App\Models\Radiology\Radiology::firstOrNew(["accessionNumber"=>$pacsData['00800050']['Value'][0]]);
                    else $radiology = \App\Models\Radiology\Radiology::firstOrNew(["hn"=>$hn,"studyDateTime"=>$studyDateTime,"modality"=>$pacsData['00800061']['Value'][0]]);

                    $radiology["hn"] = $hn;
                    $radiology["accessionNumber"] = $pacsData['00800050']['Value'][0];
                    $radiology["modality"] = $pacsData['00800061']['Value'][0];
                    $radiology["studyDateTime"] = $studyDateTime;
                    $radiology["uid"] = $pacsData['0020000D']['Value'][0];
                    $radiology["referringDoctor"] = $pacsData['00080090']['Value'][0][Alphabetic];
                    $radiology["imageCount"] = $pacsData['00201208']['Value'][0];

                    $radiology->save();
                }
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }
        }
    }
}
