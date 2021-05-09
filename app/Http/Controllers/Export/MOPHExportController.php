<?php

namespace App\Http\Controllers\Export;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;

class MOPHExportController extends Controller
{
    public static function sendUpdateImmunizationData($force=false) {
        Log::info('Begin export data to MOPH IC');

        $ApiMethod = "POST";
        $ApiUrl = (config('app.env')=="PROD") ? 'https://cvp1.moph.go.th/api/UpdateImmunization' : 'https://cloud4.hosxp.net/api/moph/UpdateImmunization';
        
        Log::info($ApiUrl);

        $requestData = [
          'headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.self::getToken(),
          ],
          'verify' => false
        ];

        if ($force) {
            $documents = \App\Models\Document\Documents::where('templateCode','cv19-vaccine-administration')
                        ->where('status','approved')
                        ->whereNotNull('encounterId')
                        ->where(function ($query) {
                            $query->where(function ($query) {
                                $query->where('created_at','<=',\Carbon\Carbon::now()->subMinutes(70))
                                    ->where('created_at','>=',\Carbon\Carbon::now()->subWeek()->startOfDay());
                            })
                            ->orWhere(function ($query) {
                                $query->doesntHave('mophsentsuccess');
                            });
                        })
                        ->get();
        } else {
            $documents = \App\Models\Document\Documents::where('templateCode','cv19-vaccine-administration')
                        ->where('status','approved')
                        ->whereNotNull('encounterId')
                        ->where('created_at','<=',\Carbon\Carbon::now()->subMinutes(70))
                        ->doesntHave('mophsent')->get();
        }

        foreach($documents as $document) {
            
            if (!$force) {
                $document->refresh();
                if (count($document->mophsent)>0) continue;
            }

            try {
                $CallData = [
                    "hospital" => [
                        "hospital_code" => "13781",
                        "hospital_name" => "โรงพยาบาลรามาธิบดี มหาวิทยาลัยมหิดล"
                    ]
                ];

                $CallData['patient'] = self::buildPatient($document->patient);
                $CallData['visit'] = self::buildVisit($document);

                $requestData['json'] = $CallData;

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
                $mophApiSent->responseData = ["Message"=>$e->getMessage(),"Document"=>$document];
                $mophApiSent->isSuccess = false;
                $mophApiSent->save();

                log::error("Error MOPH Export for document ID ".$document->id,["Message"=>$e->getMessage(),"Document"=>$document]);
            }
        }
        Log::info('Finish export data to MOPH IC');
    }

    public static function buildPatient($patient) {
        $mophPatient = \App\Models\Moph\MophPatients::firstOrCreate(['hn'=>$patient['mrn']],['guid'=>Str::uuid()->toString()]);
        $target = self::getTarget($patient['citizenId']);

        $patientData = [
            "CID" => $patient['citizenId'],
            "hn" => $patient['mrn'],
            "patient_guid" => '{'.strtoupper($mophPatient->guid).'}',
            "prefix" => $patient['initial'],
            "first_name" => $patient['firstName'],
            "last_name" => $patient['lastName'],
            "gender" => (isset($target["person"]["gender"])) ? $target["person"]["gender"] : "",
            "birth_date" => (isset($target["person"]["birth_date"])) ? $target["person"]["birth_date"] : "",
            "marital_status_id" =>  null,
            "address" => "",
            "moo" => "",
            "road" => "",
            "chw_code" => "",
            "amp_code" => (isset($target["person"]["district_code"])) ? $target["person"]["district_code"] : "",
            "tmb_code" => (isset($target["person"]["province_code"])) ? $target["person"]["province_code"] : "",
            "mobile_phone" => (isset($target["person"]["mobile_phone"])) ? $target["person"]["mobile_phone"] : ""
        ];

        if ($patientData["gender"] == "") $patientData["gender"] = ($patient['gender']=="ชาย") ? 1 : 2;
        if ($patientData["birth_date"] == "") $patientData["birth_date"] = $patient["dateOfBirth"]->format('Y-m-d');

        return $patientData;
    }

    public static function buildVisit($document) {
        $mophEncounter = \App\Models\Moph\MophEncounters::firstOrCreate(['encounterId'=>$document['encounterId']],['guid'=>Str::uuid()->toString()]);

        $previousVisitCount = \App\Models\Document\Documents::where('hn',$document->hn)
                        ->where('templateCode','cv19-vaccine-administration')
                        ->where('status','approved')
                        ->whereDate('created_at','<',$document->created_at)
                        ->whereNotNull('encounterId')
                        ->count();
        
        if ($previousVisitCount==0) {
            $target = self::getTarget($document->patient['citizenId']);
            $history = collect($target["vaccine_history"]);
            $previousVisitCount = $history->filter(function($value) use ($document) {
                                        return \Carbon\Carbon::parse($value["immunization_datetime"])->endOfDay()->isBefore($document->created_at);
                                    })->count();
        }

        $nextVisit = \App\Models\Document\Documents::where('hn',$document->hn)
                        ->where('templateCode','cv19-vaccine-administration')
                        ->where('status','approved')
                        ->whereDate('created_at','>',$document->created_at)
                        ->whereNotNull('encounterId')
                        ->orderBy('id')
                        ->first();

        $discharges = \App\Models\Document\Documents::where('hn',$document->hn)
                        ->where('templateCode','cv19-vaccine-discharge')
                        ->where('status','approved')
                        ->whereDate('created_at',$document->created_at)
                        ->whereNotNull('encounterId')
                        ->get();

        $reactions = \App\Models\Document\Documents::where('hn',$document->hn)
                        ->where('templateCode','cv19-vaccine-adverseevents')
                        ->where('status','approved')
                        ->whereDate('created_at','>=',$document->created_at)
                        ->whereNotNull('encounterId');
        if ($nextVisit) $reactions = $reactions->whereDate('created_at','<',$nextVisit->created_at);
        $reactions = $reactions->get();

        $vaccine = \App\Models\Master\MasterItems::where('groupKey','covid19Vaccine')->where('itemCode',$document->data["productCode"])->first();
        $vaccineRoute = \App\Models\Master\MasterItems::where('groupKey','covid19VaccineAdminRoute')->where('itemCode',$document->data["adminRoute"])->first();

        $personnel = \App\Http\Controllers\ApiController::RemoteSOAPApiRaw('http://staffservice.rama.mahidol.ac.th:8080/StaffService/services/StaffService?wsdl','getStaffInfoById',[['staffId'=>$document->created_by]],null,null,30*60);
        $personnel = $personnel["returnModels"];

        $visit = [
           "visit_guid" => '{'.strtoupper($mophEncounter->guid).'}',
           "visit_ref_code" => $document->hn."-".$document->created_at->format('Ymd'),
           "visit_datetime" => $document->created_at->format('Y-m-d H:i:s'),
           "claim_fund_pcode" => "A1",
           "visit_observation" => [
              "systolic_blood_pressure" => (!empty($document->data["SBP"])) ? $document->data["SBP"] : 0,
              "diastolic_blood_pressure" => (!empty($document->data["DBP"])) ? $document->data["DBP"] : 0,
              "body_weight_kg" => (!empty($document->data["BW"])) ? $document->data["BW"] : 0,
              "body_height_cm" => (!empty($document->data["high"])) ? $document->data["high"] : 0,
              "temperature" => (!empty($document->data["temp"])) ? $document->data["temp"] : 0
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
                    "license_number" => $personnel["LICENSE_NUMBER"],
                    "name" => $personnel["FIRSTNAME"]." ".$personnel["LASTNAME"],
                    "role" => $personnel["POSITION_NAME"],
                 ],
                 "immunization_plan_ref_code" => "",
                 "immunization_plan_schedule_ref_code" => ""
              ]
           ],
           "visit_immunization_reaction" => [],
           "appointment" => self::buildAppointment($document)
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
                   "reaction_detail_text" => "ไข้ต่ำ ๆ หรือ ปวดศีรษะ",
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
                   "reaction_detail_text" => "ปวด/บวม/แดง/ร้อน/คัน บริเวณที่ฉีด",
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
                   "reaction_detail_text" => "อ่อนเพลีย/ไม่มีแรง",
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
                   "reaction_detail_text" => "ไม่สบายตัว ปวดเมื่อย",
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
                   "reaction_detail_text" => "คลื่นไส้ อาเจียน ไม่เกิน 5 ครั้ง",
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
                   "reaction_detail_text" => "ผื่นแดงเล็กน้อย",
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
                   "reaction_detail_text" => 'ไข้สูง หนาวสั่น ปวดศีรษะรุนแรง',
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
                   "reaction_detail_text" => 'เหนื่อย แน่นหน้าอก หายใจไม่สะดวก หรือ หายใจไม่ออก',
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
                   "reaction_detail_text" => 'อาเจียน มากกว่า 5 ครั้ง',
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
                   "reaction_detail_text" => 'ผื่นขึ้นทั้งตัว ผิวหนังลอก',
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
                   "reaction_detail_text" => 'มีจุด (จ้ำ) เลือดออกจำนวนมาก',
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
                   "reaction_detail_text" => 'ใบหน้าเบี้ยว หรือ ปากเบี้ยว',
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
                   "reaction_detail_text" => 'แขนขาอ่อนแรง กล้ามเนื้ออ่อนแรง ไม่สามารถทรงตัวได้',
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
                   "reaction_detail_text" => 'ชัก หรือ หมดสติ',
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
               "reaction_detail_text" => "ไข้ต่ำ ๆ หรือ ปวดศีรษะ",
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
               "reaction_detail_text" => "ปวด/บวม/แดง/ร้อน/คัน บริเวณที่ฉีด",
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
               "reaction_detail_text" => "อ่อนเพลีย/ไม่มีแรง",
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
               "reaction_detail_text" => "ไม่สบายตัว ปวดเมื่อย",
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
               "reaction_detail_text" => "คลื่นไส้ อาเจียน ไม่เกิน 5 ครั้ง",
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
               "reaction_detail_text" => "ผื่นแดงเล็กน้อย",
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
               "reaction_detail_text" => (!empty($document->data["isSymptomMildOthersDetail"])) ? $document->data["isSymptomMildOthersDetail"] : null,
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
               "reaction_detail_text" => 'ไข้สูง หนาวสั่น ปวดศีรษะรุนแรง',
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
               "reaction_detail_text" => 'เหนื่อย แน่นหน้าอก หายใจไม่สะดวก หรือ หายใจไม่ออก',
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
               "reaction_detail_text" => 'อาเจียน มากกว่า 5 ครั้ง',
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
               "reaction_detail_text" => 'ผื่นขึ้นทั้งตัว ผิวหนังลอก',
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
               "reaction_detail_text" => 'มีจุด (จ้ำ) เลือดออกจำนวนมาก',
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
               "reaction_detail_text" => 'ใบหน้าเบี้ยว หรือ ปากเบี้ยว',
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
               "reaction_detail_text" => 'แขนขาอ่อนแรง กล้ามเนื้ออ่อนแรง ไม่สามารถทรงตัวได้',
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
               "reaction_detail_text" => 'ชัก หรือ หมดสติ',
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
               "reaction_detail_text" => (!empty($document->data["isSymptomSevereOthersDetail"])) ? $document->data["isSymptomSevereOthersDetail"] : null,
               "vaccine_reaction_type_id" => 2,
               "reaction_date" => $document->created_at->format('Y-m-d'),
               "vaccine_reaction_stage_id" => 2,
               "vaccine_reaction_symptom_id" => 10
            ];
        }

        return $reaction;
    }

    public static function buildAppointment($document) {
        $appointmentList = \App\Http\Controllers\ApiController::RemoteRESTApi('PatientAppointments',['mrn'=>$document->hn]);
        if ($appointmentList["success"]) {
            $appList = collect($appointmentList["returnModels"]["appointmentList"]);
            $appList = $appList->where('sdloc','ODI01')->filter(function($value) use ($document) {
                            return \Carbon\Carbon::createFromFormat('Y-m-d',$value["appointmentDate"])->isAfter($document->created_at);
                        })->first();
            if ($appList) {
                $appointmentDetail = \App\Http\Controllers\ApiController::RemoteRESTApi('PatientAppointmentDetail',['mrn'=>$document->hn,'appointmentId'=>$appList['appointmentId'],'appointmentType'=>$appList['appointmentTypeCode'],'language'=>'TH']);
                $appointmentDetail = $appointmentDetail['returnModels'];

                if (!empty($appointmentDetail)) {
                    $personnel = \App\Http\Controllers\ApiController::RemoteSOAPApiRaw('http://staffservice.rama.mahidol.ac.th:8080/StaffService/services/StaffService?wsdl','getStaffInfoById',[['staffId'=>$appointmentDetail['staffId']]],null,null,30*60);
                    $personnel = $personnel["returnModels"];

                    return [
                        [
                            "appointment_ref_code" => $appointmentDetail['appointmentId'],
                            "appointment_datetime" => $appointmentDetail['appointmentDate'].' '.$appointmentDetail['appointmentTime'],
                            "appointment_note" => $appointmentDetail['moreDetail'],
                            "appointment_cause" => $appointmentDetail['activityName'],
                            "provis_aptype_code" => "C19",
                            "practitioner" => [
                               "license_number" => $personnel["LICENSE_NUMBER"],
                               "name" => $personnel["FIRSTNAME"]." ".$personnel["LASTNAME"],
                               "role" => "แพทย์"
                            ]
                        ]
                    ];
                } else {
                    return [];
                }
            } else {
                return [];
            }
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
            "hospital_code" => env('HOSPITAL_CODE', '13781'),
        ];
        try {
            $client = new \GuzzleHttp\Client();
            $res = $client->request($ApiMethod,$ApiUrl,$requestData);

            $httpResponseCode = $res->getStatusCode();
            $httpResponseReason = $res->getReasonPhrase();

            if ($httpResponseCode==200) {
                $result = (String)$res->getBody();
                Cache::put($cacheKey,$result,15*60);
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
            "hospital_code" => "13781",
        ];

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
}
