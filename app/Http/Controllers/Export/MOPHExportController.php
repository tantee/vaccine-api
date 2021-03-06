<?php

namespace App\Http\Controllers\Export;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Document\Documents;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Master\MasterController;

class MOPHExportController extends Controller
{
    public static function sendBatchUpdateData() {
        Log::info('Nightly export data to MOPH IC');

        $documents = \App\Models\Document\Documents::where('templateCode','cv19-vaccine-administration')
                        ->where('status','approved')
                        ->where(function ($query) {
                            $query->where(function ($query) {
                                $query->where('created_at','<=',\Carbon\Carbon::now()->subMinutes(60))
                                    ->where('created_at','>=',\Carbon\Carbon::now()->subWeeks(2)->startOfDay());
                            })
                            ->orWhere(function ($query) {
                                $query->doesntHave('mophsentsuccess');
                            });
                        })
                        ->get();

        foreach($documents as $document) {
            \App\Jobs\Covid19\SendDataToMoph::dispatch($document);
        }
    }

    public static function resendFailedData() {
        Log::info('Resend failed data to MOPH IC');

        $documents = \App\Models\Document\Documents::where('templateCode','cv19-vaccine-administration')
                        ->where('status','approved')
                        ->doesntHave('mophsentsuccess')
                        ->get();

        foreach($documents as $document) {
            \App\Jobs\Covid19\SendDataToMoph::dispatch($document);
        }
    }

    public static function sendSingleDataFromId($documentId) {
        $document = \App\Models\Document\Documents::find($documentId);
        if ($document && $document->templateCode=='cv19-vaccine-administration' && $document->status=='approved') {
            self::sendSingleData($document);
        }
    }

    public static function sendSingleData(Documents $document) {
        $ApiMethod = "POST";
        $ApiUrl = (config('app.env')=="PROD") ? 'https://cvp1.moph.go.th/api/UpdateImmunization' : 'https://cloud4.hosxp.net/api/moph/UpdateImmunization';

        $requestData = [
          'headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.self::getToken(),
          ],
          'verify' => false
        ];

        try {
            $CallData = [
                "hospital" => [
                    "hospital_code" => env('HOSPITAL_CODE',''),
                    "hospital_name" => env('HOSPITAL_NAME','')
                ]
            ];

            $CallData['patient'] = self::buildPatient($document->patient);
            $CallData['visit'] = self::buildVisit($document);

            $previousSent = \App\Models\Moph\MophApiSents::where('documentId',$document->id)->orderBy('id','desc')->first();
            if ($previousSent) {
                if ($CallData == $previousSent->requestData && $previousSent->isSuccess) return;
            }

            $requestData['json'] = $CallData;
            $requestData['timeout'] = 5;

            $client = new \GuzzleHttp\Client();
            $res = $client->request($ApiMethod,$ApiUrl,$requestData);

            $httpResponseCode = $res->getStatusCode();
            $httpResponseReason = $res->getReasonPhrase();
            $ApiData = json_decode((String)$res->getBody(),true);

            $mophApiSent = new \App\Models\Moph\MophApiSents();
            $mophApiSent->documentId = $document->id;
            $mophApiSent->requestData = $CallData;
            $mophApiSent->responseData = $ApiData;
            $mophApiSent->isSuccess = (isset($ApiData["result"]["immunization_data_error"])) ? false : true;
            $mophApiSent->save();

            Log::info('Export data to MOPH IC, document ID '.$document->id);
        } catch(\Exception $e) {

            $mophApiSent = new \App\Models\Moph\MophApiSents();
            $mophApiSent->documentId = $document->id;
            $mophApiSent->requestData = $CallData;
            $responseData = ["Message"=>$e->getMessage(),"Document"=>$document];
            if ($e instanceof \GuzzleHttp\Exception\RequestException) {
                if ($e->hasResponse()) {
                    $responseData["Response"] = json_decode((String)$e->getResponse()->getBody(),true);
                }
            }
            $mophApiSent->responseData = $responseData;
            $mophApiSent->isSuccess = false;
            $mophApiSent->save();

            log::error("Error MOPH Export for document ID ".$document->id,["Message"=>$e->getMessage(),"Document"=>$document]);
        }
    }

    public static function buildPatient($patient) {
        $mophPatient = \App\Models\Moph\MophPatients::firstOrCreate(['hn'=>$patient->hn],['guid'=>Str::uuid()->toString()]);

        $address = $patient->Addresses()->orderBy('addressType')->first();

        $patientData = [
            "CID" => $patient->hn,
            "hn" => $patient->hn,
            "patient_guid" => '{'.strtoupper($mophPatient->guid).'}',
            "prefix" => MasterController::translateMaster('$NamePrefix',$patient->name_th->namePrefix),
            "first_name" => $patient->name_th->firstName,
            "last_name" => $patient->name_th->lastName,
            "gender" => $patient->sex,
            "birth_date" => $patient->dateOfBirth->format('Y-m-d'),
            "marital_status_id" =>  null,
            "address" => ($address && trim($address->address." ".$address->soi)) ? trim($address->address." ".$address->soi) : "",
            "moo" => ($address && $address->moo) ? trim(preg_replace('/(????????????(?:?????????)?[\s]*)/m','',$address->moo)) : "",
            "road" => ($address && $address->street) ? $address->street : "",
            "chw_code" => ($address && $address->province) ? $address->province : "",
            "amp_code" => ($address && $address->district) ? substr($address->district,-2) : "",
            "tmb_code" => ($address && $address->subdistrict) ? substr($address->subdistrict,-2) : "",
            "mobile_phone" => $patient->primaryMobileNo
        ];

        return $patientData;
    }

    public static function buildVisit($document) {
        $mophEncounter = \App\Models\Moph\MophEncounters::firstOrCreate(['encounterId'=>$document->created_at->format('Ymd').$document->hn],['guid'=>Str::uuid()->toString()]);

        $previousVisitCount = \App\Models\Document\Documents::where('hn',$document->hn)
                        ->where('templateCode','cv19-vaccine-administration')
                        ->where('status','approved')
                        ->whereDate('created_at','<',$document->created_at)
                        ->count();

        $target = self::getTarget($document->hn);
        $previousMophVisitCount = 0;
        if ($target) {
            try {
                $history = collect($target["vaccine_history"]);
                $previousMophVisitCount = $history->filter(function($value) use ($document) {
                                            return \Carbon\Carbon::parse($value["immunization_datetime"])->timezone(config('app.timezone'))->endOfDay()->isBefore($document->created_at);
                                        })->count();
            } catch(\Exception $e) {
                $previousMophVisitCount = 0;
            }
        }
        
        if ($previousVisitCount<$previousMophVisitCount) $previousVisitCount = $previousMophVisitCount;

        $nextVisit = \App\Models\Document\Documents::where('hn',$document->hn)
                        ->where('templateCode','cv19-vaccine-administration')
                        ->where('status','approved')
                        ->whereDate('created_at','>',$document->created_at)
                        ->orderBy('id')
                        ->first();

        $discharges = \App\Models\Document\Documents::where('hn',$document->hn)
                        ->where('templateCode','cv19-vaccine-discharge')
                        ->where('status','approved')
                        ->whereDate('created_at',$document->created_at)
                        ->get();

        $reactions = \App\Models\Document\Documents::where('hn',$document->hn)
                        ->where('templateCode','cv19-vaccine-adverseevents')
                        ->where('status','approved')
                        ->whereDate('created_at','>=',$document->created_at);
        if ($nextVisit) $reactions = $reactions->whereDate('created_at','<',$nextVisit->created_at);
        $reactions = $reactions->get();

        $vaccine = \App\Models\Master\MasterItems::where('groupKey','covid19Vaccine')->where('itemCode',$document->data["productCode"])->first();
        $vaccineRoute = \App\Models\Master\MasterItems::where('groupKey','covid19VaccineAdminRoute')->where('itemCode',$document->data["adminRoute"])->first();

        $personnel = \App\Models\User\Users::where('username',$document->created_by)->first();

        $visit = [
           "visit_guid" => '{'.strtoupper($mophEncounter->guid).'}',
           "visit_ref_code" => $document->hn."-".$document->created_at->format('Ymd'),
           "visit_datetime" => $document->created_at->format('Y-m-d H:i:s'),
           "claim_fund_pcode" => "A1",
           "visit_observation" => [
              "systolic_blood_pressure" => (!empty($document->data["bloodPressureSystolic"])) ? $document->data["bloodPressureSystolic"] : 0,
              "diastolic_blood_pressure" => (!empty($document->data["bloodPressureDiastolic"])) ? $document->data["bloodPressureDiastolic"] : 0,
              "body_weight_kg" => (!empty($document->data["weight"])) ? $document->data["weight"] : 0,
              "body_height_cm" => (!empty($document->data["height"])) ? $document->data["height"] : 0,
              "temperature" => (!empty($document->data["temperature"])) ? $document->data["temperature"] : 0
           ],
           "visit_immunization" => [
              [
                 "visit_immunization_ref_code" => $document->hn."-".$document->created_at->format('Ymd'),
                 "immunization_datetime" => (!empty($document->data["adminDateTime"])) ? $document->data["adminDateTime"] : null,
                 "vaccine_code" => (!empty($vaccine->properties["moph_vaccine_code"])) ? $vaccine->properties["moph_vaccine_code"] : null,
                 "lot_number" => (!empty($document->data["lotNo"])) ? $document->data["lotNo"] : null,
                 "expiration_date" => (!empty($document->data["expDate"])) ? ((strpos($document->data["expDate"],'/')) ? \Carbon\Carbon::createFromFormat('d/m/y',$document->data["expDate"])->format('Y-m-d') :$document->data["expDate"]) : null,
                 "vaccine_note" => $vaccine->itemValue,
                 "vaccine_ref_name" => (!empty($vaccine->properties["moph_vaccine_name"])) ? $vaccine->properties["moph_vaccine_name"] : null,
                 "serial_no" => (!empty($document->data["serialNo"])) ? $document->data["serialNo"] : null,
                 "vaccine_manufacturer" => (!empty($vaccine->properties["moph_vaccine_manufacturer"])) ? $vaccine->properties["moph_vaccine_manufacturer"] : null,

                 "vaccine_plan_no" => $previousVisitCount+1,
                 "vaccine_route_name" => $vaccineRoute->itemValue,
                 "practitioner" => [
                    "license_number" => $personnel->licenseNo ?? " ",
                    "name" => $personnel->name,
                    "role" => MasterController::translateMaster('$UserPosition',$personnel->position),
                 ],
                 "immunization_plan_ref_code" => "",
                 "immunization_plan_schedule_ref_code" => ""
              ]
           ],
           "visit_immunization_reaction" => [],
           "appointment" => ($previousVisitCount<1) ? self::buildAppointment($document) : []
        ];

        foreach($discharges as $discharge) $visit["visit_immunization_reaction"] = array_merge($visit["visit_immunization_reaction"],self::buildReactionDischarge($discharge,$document->hn."-".$document->created_at->format('Ymd')));
        foreach($reactions as $reaction) $visit["visit_immunization_reaction"] = array_merge($visit["visit_immunization_reaction"],self::buildReaction($reaction,$document->hn."-".$document->created_at->format('Ymd')));

        return $visit;
    }

    public static function buildReactionDischarge($document,$visitImmunizationRefCode) {
        if (isset($document->data["isSymptomAfterVaccine"]) && $document->data["isSymptomAfterVaccine"]) {
            $reaction = [];
            if (isset($document->data["isSymptomMildFever"]) && $document->data["isSymptomMildFever"]) {
                $reaction[] = [
                   "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-MildFever',
                   "visit_immunization_ref_code" => $visitImmunizationRefCode,
                   "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
                   "reaction_detail_text" => "?????????????????? ??? ???????????? ????????????????????????",
                   "vaccine_reaction_type_id" => 1,
                   "reaction_date" => $document->created_at->format('Y-m-d'),
                   "vaccine_reaction_stage_id" => 1,
                   "vaccine_reaction_symptom_id" => 2
                ];
            }
            if (isset($document->data["isSymptomMildInflammation"]) && $document->data["isSymptomMildInflammation"]) {
                $reaction[] = [
                   "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-MildInflammation',
                   "visit_immunization_ref_code" => $visitImmunizationRefCode,
                   "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
                   "reaction_detail_text" => "?????????/?????????/?????????/????????????/????????? ????????????????????????????????????",
                   "vaccine_reaction_type_id" => 1,
                   "reaction_date" => $document->created_at->format('Y-m-d'),
                   "vaccine_reaction_stage_id" => 1,
                   "vaccine_reaction_symptom_id" => 1
                ];
            }
            if (isset($document->data["isSymptomMildFatique"]) && $document->data["isSymptomMildFatique"]) {
                $reaction[] = [
                   "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-MildFatique',
                   "visit_immunization_ref_code" => $visitImmunizationRefCode,
                   "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
                   "reaction_detail_text" => "???????????????????????????/????????????????????????",
                   "vaccine_reaction_type_id" => 1,
                   "reaction_date" => $document->created_at->format('Y-m-d'),
                   "vaccine_reaction_stage_id" => 1,
                   "vaccine_reaction_symptom_id" => 4
                ];
            }
            if (isset($document->data["isSymptomMildMalaise"]) && $document->data["isSymptomMildMalaise"]) {
                $reaction[] = [
                   "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-MildMalaise',
                   "visit_immunization_ref_code" => $visitImmunizationRefCode,
                   "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
                   "reaction_detail_text" => "?????????????????????????????? ???????????????????????????",
                   "vaccine_reaction_type_id" => 1,
                   "reaction_date" => $document->created_at->format('Y-m-d'),
                   "vaccine_reaction_stage_id" => 1,
                   "vaccine_reaction_symptom_id" => 5
                ];
            }
            if (isset($document->data["isSymptomMildNausea"]) && $document->data["isSymptomMildNausea"]) {
                $reaction[] = [
                   "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-MildNausea',
                   "visit_immunization_ref_code" => $visitImmunizationRefCode,
                   "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
                   "reaction_detail_text" => "???????????????????????? ????????????????????? ????????????????????? 5 ???????????????",
                   "vaccine_reaction_type_id" => 1,
                   "reaction_date" => $document->created_at->format('Y-m-d'),
                   "vaccine_reaction_stage_id" => 1,
                   "vaccine_reaction_symptom_id" => 6
                ];
            }
            if (isset($document->data["isSymptomMildRash"]) && $document->data["isSymptomMildRash"]) {
                $reaction[] = [
                   "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-MildRash',
                   "visit_immunization_ref_code" => $visitImmunizationRefCode,
                   "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
                   "reaction_detail_text" => "?????????????????????????????????????????????",
                   "vaccine_reaction_type_id" => 1,
                   "reaction_date" => $document->created_at->format('Y-m-d'),
                   "vaccine_reaction_stage_id" => 1,
                   "vaccine_reaction_symptom_id" => 9
                ];
            }
            if (isset($document->data["isSymptomMildOthers"]) && $document->data["isSymptomMildOthers"]) {
                $reaction[] = [
                   "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-MildOthers',
                   "visit_immunization_ref_code" => $visitImmunizationRefCode,
                   "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
                   "reaction_detail_text" => (!empty($document->data["isSymptomMildOthersDetail"])) ? $document->data["isSymptomMildOthersDetail"] : null,
                   "vaccine_reaction_type_id" => 1,
                   "reaction_date" => $document->created_at->format('Y-m-d'),
                   "vaccine_reaction_stage_id" => 1,
                   "vaccine_reaction_symptom_id" => 10
                ];
            }

            if (isset($document->data["isSymptomSevereFever"]) && $document->data["isSymptomSevereFever"]) {
                $reaction[] = [
                   "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-SevereFever',
                   "visit_immunization_ref_code" => $visitImmunizationRefCode,
                   "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
                   "reaction_detail_text" => '?????????????????? ???????????????????????? ??????????????????????????????????????????',
                   "vaccine_reaction_type_id" => 2,
                   "reaction_date" => $document->created_at->format('Y-m-d'),
                   "vaccine_reaction_stage_id" => 1,
                   "vaccine_reaction_symptom_id" => 2
                ];
            }
            if (isset($document->data["isSymptomSevereDyspnea"]) && $document->data["isSymptomSevereDyspnea"]) {
                $reaction[] = [
                   "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-SevereDyspnea',
                   "visit_immunization_ref_code" => $visitImmunizationRefCode,
                   "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
                   "reaction_detail_text" => '????????????????????? ?????????????????????????????? ??????????????????????????????????????? ???????????? ?????????????????????????????????',
                   "vaccine_reaction_type_id" => 2,
                   "reaction_date" => $document->created_at->format('Y-m-d'),
                   "vaccine_reaction_stage_id" => 1,
                   "vaccine_reaction_symptom_id" => 4
                ];
            }
            if (isset($document->data["isSymptomSevereVomitting"]) && $document->data["isSymptomSevereVomitting"]) {
                $reaction[] = [
                   "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-SevereVomitting',
                   "visit_immunization_ref_code" => $visitImmunizationRefCode,
                   "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
                   "reaction_detail_text" => '????????????????????? ????????????????????? 5 ???????????????',
                   "vaccine_reaction_type_id" => 2,
                   "reaction_date" => $document->created_at->format('Y-m-d'),
                   "vaccine_reaction_stage_id" => 1,
                   "vaccine_reaction_symptom_id" => 7
                ];
            }
            if (isset($document->data["isSymptomSevereRash"]) && $document->data["isSymptomSevereRash"]) {
                $reaction[] = [
                   "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-SevereRash',
                   "visit_immunization_ref_code" => $visitImmunizationRefCode,
                   "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
                   "reaction_detail_text" => '????????????????????????????????????????????? ??????????????????????????????',
                   "vaccine_reaction_type_id" => 2,
                   "reaction_date" => $document->created_at->format('Y-m-d'),
                   "vaccine_reaction_stage_id" => 1,
                   "vaccine_reaction_symptom_id" => 9
                ];
            }
            if (isset($document->data["isSymptomSevereEcchymosis"]) && $document->data["isSymptomSevereEcchymosis"]) {
                $reaction[] = [
                   "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-SevereEcchymosis',
                   "visit_immunization_ref_code" => $visitImmunizationRefCode,
                   "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
                   "reaction_detail_text" => '??????????????? (?????????) ????????????????????????????????????????????????',
                   "vaccine_reaction_type_id" => 2,
                   "reaction_date" => $document->created_at->format('Y-m-d'),
                   "vaccine_reaction_stage_id" => 1,
                   "vaccine_reaction_symptom_id" => 10
                ];
            }
            if (isset($document->data["isSymptomSevereFacialPalsy"]) && $document->data["isSymptomSevereFacialPalsy"]) {
                $reaction[] = [
                   "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-SevereFacialPalsy',
                   "visit_immunization_ref_code" => $visitImmunizationRefCode,
                   "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
                   "reaction_detail_text" => '???????????????????????????????????? ???????????? ???????????????????????????',
                   "vaccine_reaction_type_id" => 2,
                   "reaction_date" => $document->created_at->format('Y-m-d'),
                   "vaccine_reaction_stage_id" => 1,
                   "vaccine_reaction_symptom_id" => 10
                ];
            }
            if (isset($document->data["isSymptomSevereWeakness"]) && $document->data["isSymptomSevereWeakness"]) {
                $reaction[] = [
                   "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-SevereWeakness',
                   "visit_immunization_ref_code" => $visitImmunizationRefCode,
                   "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
                   "reaction_detail_text" => '???????????????????????????????????? ??????????????????????????????????????????????????? ??????????????????????????????????????????????????????',
                   "vaccine_reaction_type_id" => 2,
                   "reaction_date" => $document->created_at->format('Y-m-d'),
                   "vaccine_reaction_stage_id" => 1,
                   "vaccine_reaction_symptom_id" => 11
                ];
            }
            if (isset($document->data["isSymptomSevereSeizure"]) && $document->data["isSymptomSevereSeizure"]) {
                $reaction[] = [
                   "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-SevereSeizure',
                   "visit_immunization_ref_code" => $visitImmunizationRefCode,
                   "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
                   "reaction_detail_text" => '????????? ???????????? ??????????????????',
                   "vaccine_reaction_type_id" => 2,
                   "reaction_date" => $document->created_at->format('Y-m-d'),
                   "vaccine_reaction_stage_id" => 1,
                   "vaccine_reaction_symptom_id" => 10
                ];
            }
            if (isset($document->data["isSymptomSevereOthers"]) && $document->data["isSymptomSevereOthers"]) {
                $reaction[] = [
                   "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-SevereOthers',
                   "visit_immunization_ref_code" => $visitImmunizationRefCode,
                   "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
                   "reaction_detail_text" => (!empty($document->data["isSymptomSevereOthersDetail"])) ? $document->data["isSymptomSevereOthersDetail"] : null,
                   "vaccine_reaction_type_id" => 2,
                   "reaction_date" => $document->created_at->format('Y-m-d'),
                   "vaccine_reaction_stage_id" => 1,
                   "vaccine_reaction_symptom_id" => 10
                ];
            }

            return $reaction;
        } else {
            return [];
        }
    }

    public static function buildReaction($document,$visitImmunizationRefCode) {
        $reaction = [];
        if (isset($document->data["isSymptomMildFever"]) && $document->data["isSymptomMildFever"]) {
            $reaction[] = [
               "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-MildFever_'.$document->id,
               "visit_immunization_ref_code" => $visitImmunizationRefCode,
               "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
               "reaction_detail_text" => "?????????????????? ??? ???????????? ????????????????????????",
               "vaccine_reaction_type_id" => 1,
               "reaction_date" => $document->created_at->format('Y-m-d'),
               "vaccine_reaction_stage_id" => 2,
               "vaccine_reaction_symptom_id" => 2
            ];
        }
        if (isset($document->data["isSymptomMildInflammation"]) && $document->data["isSymptomMildInflammation"]) {
            $reaction[] = [
               "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-MildInflammation_'.$document->id,
               "visit_immunization_ref_code" => $visitImmunizationRefCode,
               "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
               "reaction_detail_text" => "?????????/?????????/?????????/????????????/????????? ????????????????????????????????????",
               "vaccine_reaction_type_id" => 1,
               "reaction_date" => $document->created_at->format('Y-m-d'),
               "vaccine_reaction_stage_id" => 2,
               "vaccine_reaction_symptom_id" => 1
            ];
        }
        if (isset($document->data["isSymptomMildFatique"]) && $document->data["isSymptomMildFatique"]) {
            $reaction[] = [
               "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-MildFatique_'.$document->id,
               "visit_immunization_ref_code" => $visitImmunizationRefCode,
               "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
               "reaction_detail_text" => "???????????????????????????/????????????????????????",
               "vaccine_reaction_type_id" => 1,
               "reaction_date" => $document->created_at->format('Y-m-d'),
               "vaccine_reaction_stage_id" => 2,
               "vaccine_reaction_symptom_id" => 4
            ];
        }
        if (isset($document->data["isSymptomMildMalaise"]) && $document->data["isSymptomMildMalaise"]) {
            $reaction[] = [
               "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-MildMalaise_'.$document->id,
               "visit_immunization_ref_code" => $visitImmunizationRefCode,
               "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
               "reaction_detail_text" => "?????????????????????????????? ???????????????????????????",
               "vaccine_reaction_type_id" => 1,
               "reaction_date" => $document->created_at->format('Y-m-d'),
               "vaccine_reaction_stage_id" => 2,
               "vaccine_reaction_symptom_id" => 5
            ];
        }
        if (isset($document->data["isSymptomMildNausea"]) && $document->data["isSymptomMildNausea"]) {
            $reaction[] = [
               "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-MildNausea_'.$document->id,
               "visit_immunization_ref_code" => $visitImmunizationRefCode,
               "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
               "reaction_detail_text" => "???????????????????????? ????????????????????? ????????????????????? 5 ???????????????",
               "vaccine_reaction_type_id" => 1,
               "reaction_date" => $document->created_at->format('Y-m-d'),
               "vaccine_reaction_stage_id" => 2,
               "vaccine_reaction_symptom_id" => 6
            ];
        }
        if (isset($document->data["isSymptomMildRash"]) && $document->data["isSymptomMildRash"]) {
            $reaction[] = [
               "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-MildRash_'.$document->id,
               "visit_immunization_ref_code" => $visitImmunizationRefCode,
               "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
               "reaction_detail_text" => "?????????????????????????????????????????????",
               "vaccine_reaction_type_id" => 1,
               "reaction_date" => $document->created_at->format('Y-m-d'),
               "vaccine_reaction_stage_id" => 2,
               "vaccine_reaction_symptom_id" => 9
            ];
        }
        if (isset($document->data["isSymptomMildOthers"]) && $document->data["isSymptomMildOthers"]) {
            $reaction[] = [
               "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-MildOthers_'.$document->id,
               "visit_immunization_ref_code" => $visitImmunizationRefCode,
               "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
               "reaction_detail_text" => (!empty($document->data["isSymptomMildOthersDetail"])) ? $document->data["isSymptomMildOthersDetail"] : "",
               "vaccine_reaction_type_id" => 1,
               "reaction_date" => $document->created_at->format('Y-m-d'),
               "vaccine_reaction_stage_id" => 2,
               "vaccine_reaction_symptom_id" => 10
            ];
        }

        if (isset($document->data["isSymptomSevereFever"]) && $document->data["isSymptomSevereFever"]) {
            $reaction[] = [
               "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-SevereFever_'.$document->id,
               "visit_immunization_ref_code" => $visitImmunizationRefCode,
               "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
               "reaction_detail_text" => '?????????????????? ???????????????????????? ??????????????????????????????????????????',
               "vaccine_reaction_type_id" => 2,
               "reaction_date" => $document->created_at->format('Y-m-d'),
               "vaccine_reaction_stage_id" => 2,
               "vaccine_reaction_symptom_id" => 2
            ];
        }
        if (isset($document->data["isSymptomSevereDyspnea"]) && $document->data["isSymptomSevereDyspnea"]) {
            $reaction[] = [
               "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-SevereDyspnea_'.$document->id,
               "visit_immunization_ref_code" => $visitImmunizationRefCode,
               "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
               "reaction_detail_text" => '????????????????????? ?????????????????????????????? ??????????????????????????????????????? ???????????? ?????????????????????????????????',
               "vaccine_reaction_type_id" => 2,
               "reaction_date" => $document->created_at->format('Y-m-d'),
               "vaccine_reaction_stage_id" => 2,
               "vaccine_reaction_symptom_id" => 4
            ];
        }
        if (isset($document->data["isSymptomSevereVomitting"]) && $document->data["isSymptomSevereVomitting"]) {
            $reaction[] = [
               "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-SevereVomitting_'.$document->id,
               "visit_immunization_ref_code" => $visitImmunizationRefCode,
               "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
               "reaction_detail_text" => '????????????????????? ????????????????????? 5 ???????????????',
               "vaccine_reaction_type_id" => 2,
               "reaction_date" => $document->created_at->format('Y-m-d'),
               "vaccine_reaction_stage_id" => 2,
               "vaccine_reaction_symptom_id" => 7
            ];
        }
        if (isset($document->data["isSymptomSevereRash"]) && $document->data["isSymptomSevereRash"]) {
            $reaction[] = [
               "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-SevereRash_'.$document->id,
               "visit_immunization_ref_code" => $visitImmunizationRefCode,
               "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
               "reaction_detail_text" => '????????????????????????????????????????????? ??????????????????????????????',
               "vaccine_reaction_type_id" => 2,
               "reaction_date" => $document->created_at->format('Y-m-d'),
               "vaccine_reaction_stage_id" => 2,
               "vaccine_reaction_symptom_id" => 9
            ];
        }
        if (isset($document->data["isSymptomSevereEcchymosis"]) && $document->data["isSymptomSevereEcchymosis"]) {
            $reaction[] = [
               "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-SevereEcchymosis_'.$document->id,
               "visit_immunization_ref_code" => $visitImmunizationRefCode,
               "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
               "reaction_detail_text" => '??????????????? (?????????) ????????????????????????????????????????????????',
               "vaccine_reaction_type_id" => 2,
               "reaction_date" => $document->created_at->format('Y-m-d'),
               "vaccine_reaction_stage_id" => 2,
               "vaccine_reaction_symptom_id" => 10
            ];
        }
        if (isset($document->data["isSymptomSevereFacialPalsy"]) && $document->data["isSymptomSevereFacialPalsy"]) {
            $reaction[] = [
               "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-SevereFacialPalsy_'.$document->id,
               "visit_immunization_ref_code" => $visitImmunizationRefCode,
               "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
               "reaction_detail_text" => '???????????????????????????????????? ???????????? ???????????????????????????',
               "vaccine_reaction_type_id" => 2,
               "reaction_date" => $document->created_at->format('Y-m-d'),
               "vaccine_reaction_stage_id" => 2,
               "vaccine_reaction_symptom_id" => 10
            ];
        }
        if (isset($document->data["isSymptomSevereWeakness"]) && $document->data["isSymptomSevereWeakness"]) {
            $reaction[] = [
               "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-SevereWeakness_'.$document->id,
               "visit_immunization_ref_code" => $visitImmunizationRefCode,
               "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
               "reaction_detail_text" => '???????????????????????????????????? ??????????????????????????????????????????????????? ??????????????????????????????????????????????????????',
               "vaccine_reaction_type_id" => 2,
               "reaction_date" => $document->created_at->format('Y-m-d'),
               "vaccine_reaction_stage_id" => 2,
               "vaccine_reaction_symptom_id" => 11
            ];
        }
        if (isset($document->data["isSymptomSevereSeizure"]) && $document->data["isSymptomSevereSeizure"]) {
            $reaction[] = [
               "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-SevereSeizure_'.$document->id,
               "visit_immunization_ref_code" => $visitImmunizationRefCode,
               "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
               "reaction_detail_text" => '????????? ???????????? ??????????????????',
               "vaccine_reaction_type_id" => 2,
               "reaction_date" => $document->created_at->format('Y-m-d'),
               "vaccine_reaction_stage_id" => 2,
               "vaccine_reaction_symptom_id" => 10
            ];
        }
        if (isset($document->data["isSymptomSevereOthers"]) && $document->data["isSymptomSevereOthers"]) {
            $reaction[] = [
               "visit_immunization_reaction_ref_code" => $document->hn.'-'.$document->created_at->format('Ymd').'-SevereOthers_'.$document->id,
               "visit_immunization_ref_code" => $visitImmunizationRefCode,
               "report_datetime" => $document->created_at->format('Y-m-d H:i:s'),
               "reaction_detail_text" => (!empty($document->data["isSymptomSevereOthersDetail"])) ? $document->data["isSymptomSevereOthersDetail"] : "",
               "vaccine_reaction_type_id" => 2,
               "reaction_date" => $document->created_at->format('Y-m-d'),
               "vaccine_reaction_stage_id" => 2,
               "vaccine_reaction_symptom_id" => 10
            ];
        }

        return $reaction;
    }

    public static function buildAppointment($document) {
        $appointment = \App\Models\Appointment\Appointments::where('hn',$document->hn)->whereDate('appointmentDateTime','>',\Carbon\Carbon::now())->orderBy('appointmentDateTime')->first();
        if ($appointment) {
            $appointmentActivity = MasterController::translateMaster('$AppointmentActivity',$appointment->appointmentActivity);
            $doctor = \App\Models\Master\Doctors::find($appointment->doctorCode);
            return [
                [
                    "appointment_ref_code" => $appointment->id,
                    "appointment_datetime" => $appointment->appointmentDateTime->format('Y-m-d H:i:s'),
                    "appointment_note" => ($appointment->note) ? $appointment->note : "-",
                    "appointment_cause" => ($appointmentActivity) ? $appointmentActivity : "?????????????????????????????????????????????????????????????????????",
                    "provis_aptype_code" => "C19",
                    "practitioner" => [
                       "license_number" => ($doctor) ? $doctor->licenseNo : " ",
                       "name" => ($doctor) ? $doctor->nameTH : " ",
                       "role" => "???????????????"
                    ]
                ]
            ];
        } else {
            return [];
        }
    }

    public static function getToken() {
        $username = env('MOPH_USERNAME', 'Laravel');
        $password = env('MOPH_PASSWORD', 'Laravel');

        $key = '$jwt@moph#';
        $hash = strtoupper(hash_hmac('sha256',$password,$key));
        $ApiUrl = "https://cvp1.moph.go.th/token";
        $ApiMethod = "GET";
        

        $cacheKey = $ApiUrl.'#'.$hash;

        if (Cache::has($cacheKey)) {
          return Cache::get($cacheKey);
        }

        $requestData = [
          'headers' => [
            'Accept' => 'application/json',
          ],
          'verify' => false
        ];

        $requestData['query'] = [
            "Action" => "get_moph_access_token",
            "user" => $username,
            "password_hash" => $hash,
            "hospital_code" => env('HOSPITAL_CODE', ''),
        ];
        $requestData['timeout'] = 5;
        try {
            $client = new \GuzzleHttp\Client();
            $res = $client->request($ApiMethod,$ApiUrl,$requestData);

            $httpResponseCode = $res->getStatusCode();
            $httpResponseReason = $res->getReasonPhrase();

            if ($httpResponseCode==200) {
                $result = (String)$res->getBody();
                Cache::put($cacheKey,$result,60);
                return $result;
            } else {
                return null;
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
          log::error("Error calling to $ApiMethod $ApiUrl.",["Message"=>$e->getMessage(),"RequestData"=>$requestData]);

          return null;
        }
    }

    public static function getTarget($cid) {
        $ApiUrl = "https://cvp1.moph.go.th/api/ImmunizationTarget";
        $ApiMethod = "GET";

        $requestData = [
            'headers' => [
              'Accept' => 'application/json',
              'Content-Type' => 'application/json',
              'Authorization' => 'Bearer '.self::getToken(),
            ],
            'verify' => false
          ];

        $requestData['query'] = [
            "cid" => $cid,
            "hospital_code" => env('HOSPITAL_CODE', ''),
        ];
        $requestData['timeout'] = 5;
        try {
            $client = new \GuzzleHttp\Client();
            $res = $client->request($ApiMethod,$ApiUrl,$requestData);

            $httpResponseCode = $res->getStatusCode();
            $httpResponseReason = $res->getReasonPhrase();

            if ($httpResponseCode==200) {
                $ApiData = json_decode((String)$res->getBody(),true);

                if ($ApiData["MessageCode"]==200) return $ApiData["result"];
                else return null;
            } else {
                return null;
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
          log::error("Error calling to $ApiMethod $ApiUrl.",["Message"=>$e->getMessage(),"RequestData"=>$requestData]);

          return null;
        }
    }

    public static function getHistory($cid) {
        $ApiUrl = "https://cvp1.moph.go.th/api/ImmunizationHistory";
        $ApiMethod = "GET";

        $requestData = [
            'headers' => [
              'Accept' => 'application/json',
              'Content-Type' => 'application/json',
              'Authorization' => 'Bearer '.self::getToken(),
            ],
            'verify' => false
          ];

        $requestData['query'] = [
            "cid" => $cid
        ];
        $requestData['timeout'] = 5;
        try {
            $client = new \GuzzleHttp\Client();
            $res = $client->request($ApiMethod,$ApiUrl,$requestData);

            $httpResponseCode = $res->getStatusCode();
            $httpResponseReason = $res->getReasonPhrase();

            if ($httpResponseCode==200) {
                $ApiData = json_decode((String)$res->getBody(),true);

                if ($ApiData["MessageCode"]==200) return $ApiData["result"];
                else return null;
            } else {
                return null;
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
          log::error("Error calling to $ApiMethod $ApiUrl.",["Message"=>$e->getMessage(),"RequestData"=>$requestData]);

          return null;
        }
    }

    public static function getCIDFromPassport($passportNo,$nationality) {
        $ApiUrl = "https://cvp1.moph.go.th/api/GetCIDFromPassportNumber";
        $ApiMethod = "GET";

        $requestData = [
            'headers' => [
              'Accept' => 'application/json',
              'Content-Type' => 'application/json',
              'Authorization' => 'Bearer '.self::getToken(),
            ],
            'verify' => false
          ];

        $requestData['query'] = [
            "passport_number" => $passportNo,
            "nationality" => $nationality
        ];
        $requestData['timeout'] = 5;
        try {
            $client = new \GuzzleHttp\Client();
            $res = $client->request($ApiMethod,$ApiUrl,$requestData);

            $httpResponseCode = $res->getStatusCode();
            $httpResponseReason = $res->getReasonPhrase();

            if ($httpResponseCode==200) {
                $ApiData = json_decode((String)$res->getBody(),true);

                if ($ApiData["MessageCode"]==200) return $ApiData["result"];
                else return null;
            } else {
                return null;
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
          log::error("Error calling to $ApiMethod $ApiUrl.",["Message"=>$e->getMessage(),"RequestData"=>$requestData]);

          return null;
        }
    }

    public static function getAppointments($appointmentDate=null) {
        if ($appointmentDate) $appointmentDate = \Carbon\Carbon::parse($appointmentDate)->timezone(config('app.timezone'));
        else $appointmentDate = \Carbon\Carbon::now();
        
        $ApiUrl = "https://cvp1.moph.go.th/api/ImmunizationHospitalSlot";
        $ApiMethod = "GET";

        $hospitalCode = (!empty(env('FIELD_HOSPITAL_CODE',''))) ? env('FIELD_HOSPITAL_CODE','') : env('HOSPITAL_CODE', '');

        $requestData = [
            'headers' => [
              'Accept' => 'application/json',
              'Content-Type' => 'application/json',
              'Authorization' => 'Bearer '.self::getToken(),
            ],
            'verify' => false
          ];

        $requestData['query'] = [
            "Action" => "GetHospitalSlotConfirmScheduleByDate",
            "date" => $appointmentDate->format("Y-m-d"),
            "hospital_code" => $hospitalCode,
        ];
        $requestData['timeout'] = 5;
        try {
            $client = new \GuzzleHttp\Client();
            $res = $client->request($ApiMethod,$ApiUrl,$requestData);

            $httpResponseCode = $res->getStatusCode();
            $httpResponseReason = $res->getReasonPhrase();

            if ($httpResponseCode==200) {
                $ApiData = json_decode((String)$res->getBody(),true);

                if ($ApiData["MessageCode"]==200) return $ApiData["result"];
                else return null;
            } else {
                return null;
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
          log::error("Error calling to $ApiMethod $ApiUrl.",["Message"=>$e->getMessage(),"RequestData"=>$requestData]);

          return null;
        }
    }

    public static function cancelAppointments($appointmentDate=null) {
        if ($appointmentDate) $appointmentDate = \Carbon\Carbon::parse($appointmentDate)->timezone(config('app.timezone'));
        else $appointmentDate = \Carbon\Carbon::now();

        $appointments = self::getAppointments($appointmentDate);

        $ApiUrl = "https://cvp1.moph.go.th/api/ImmunizationHospitalSlot";
        $ApiMethod = "GET";

        $hospitalCode = (!empty(env('FIELD_HOSPITAL_CODE',''))) ? env('FIELD_HOSPITAL_CODE','') : env('HOSPITAL_CODE', '');

        $requestData = [
            'headers' => [
              'Accept' => 'application/json',
              'Content-Type' => 'application/json',
              'Authorization' => 'Bearer '.self::getToken(),
            ],
            'verify' => false
        ];

        $requestData['query'] = [
            "Action" => "CancelAppointmentSlot",
            "hospital_code" => $hospitalCode,
        ];

        $requestData['timeout'] = 5;

        foreach ($appointments as $appointment) {
            $requestData["query"]["cid"] = $appointment["cid"];
            $requestData["query"]["hospital_appointment_slot_id "] = $appointment["hospital_appointment_slot_id"];

            try {
                $client = new \GuzzleHttp\Client();
                $res = $client->request($ApiMethod,$ApiUrl,$requestData);

                $httpResponseCode = $res->getStatusCode();
                $httpResponseReason = $res->getReasonPhrase();

                log::info('Remove appointment for '.$appointment["cid"]);
            } catch (\GuzzleHttp\Exception\RequestException $e) {
              log::error("Error calling to $ApiMethod $ApiUrl.",["Message"=>$e->getMessage(),"RequestData"=>$requestData]);
            }
        }

        return self::getAppointments($appointmentDate);
    }

    public static function checkWhitelists() {
        $whitelists = \App\Models\Moph\Whitelists::whereNull('mophTarget')->limit(500000)->get();
        
        foreach($whitelists as $whitelist) {
            \App\Jobs\Covid19\CheckWhiteList::dispatch($whitelist);
        }
    }

    public static function checkWhitelistSingle($whitelist) {
        $ApiUrl = "https://cvp1.moph.go.th/api/ImmunizationTarget";
        $ApiMethod = "GET";

        $requestData = [
            'headers' => [
              'Accept' => 'application/json',
              'Content-Type' => 'application/json',
              'Authorization' => 'Bearer '.self::getToken(),
            ],
            'verify' => false
          ];

        $requestData['query'] = [
            "cid" => $whitelist->cid,
            "hospital_code" => env('HOSPITAL_CODE', ''),
        ];
        $requestData['timeout'] = 5;
        try {
            $client = new \GuzzleHttp\Client();
            $res = $client->request($ApiMethod,$ApiUrl,$requestData);

            $httpResponseCode = $res->getStatusCode();
            $httpResponseReason = $res->getReasonPhrase();

            $mophTarget = json_decode((String)$res->getBody(),true);

            $whitelist->mophTarget = $mophTarget;
            $whitelist->isAppoint = 0;
            $whitelist->isVaccine = 0;

            if ($mophTarget["MessageCode"]==200) {
                $result = $mophTarget["result"];
                if (isset($result["confirm_appointment_slot_count"]) && $result["confirm_appointment_slot_count"]>0) {
                    $whitelist->isAppoint = 1;
                }
                if (isset($result["vaccine_history_count"]) && $result["vaccine_history_count"]>0) {
                    $whitelist->isVaccine = 1;
                }
            }
            $whitelist->save();
            log::info($whitelist->cid);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if ($e->hasResponse()) {
                $mophTarget = json_decode((String)$e->getResponse()->getBody(),true);

                $whitelist->mophTarget = $mophTarget;
                $whitelist->isAppoint = 0;
                $whitelist->isVaccine = 0;
                $whitelist->save();
                log::error($whitelist->cid);
            }
        }
    }
}
