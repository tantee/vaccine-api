<?php

namespace App\Http\Controllers\Covid19;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Export\MOPHExportController;

class VaccineController extends Controller
{
    public static function checkConsentReady($hn) {
        $readyStatus = 'no-consent';
        $consentForm = \App\Models\Document\Documents::where('hn',$hn)->where('templateCode','cv19-vaccine-consent')
                        ->whereDate('created_at','>=',\Carbon\Carbon::now()->subDays(1))
                        ->where('status','approved')
                        ->orderBy('created_at','desc')
                        ->first();
        if ($consentForm) {
            if ($consentForm->data && isset($consentForm->data["covid19VaccineRisk"])) {
                $readyStatus = $consentForm->data["covid19VaccineRisk"];
            }
        }

        return $readyStatus;
    }

    public static function checkReadiness($hn) {
        $checkList = [
            "consentCheck" => 'no-consent',
            "mophTargetCheck" => false,
            "morhTargetDetail" => null,
            "mophVaccineHistory" => [],
            "appointmentToday" => false,
            "appointmentReach" => false,
            "appointmentDateTime" => null,
            "appointmentSource" => null,
            "vaccineHistoryCount" => 0,
            "vaccineSuggestion" => null,
            "vaccineSuggestionColor" => null,
            "cautions" => [],
        ];

        $checkList["consentCheck"] = self::checkConsentReady($hn);

        $patient = \App\Models\Patient\Patients::find($hn);
        if($patient) {
            $checkList["peopleIdCheck"] = (isset($patient->hn) && (strlen($patient->hn)==13) && is_numeric($patient->hn));
            $checkList["peopleIdDetail"] = $patient->personId;

            $age = \Carbon\Carbon::now()->diffAsCarbonInterval($patient->dateOfBirth);
            if ($age->y>59 || ($age->y==59 && $age->m>=10)) $checkList["cautions"][] = '> 59 ปี 10 เดือน';

            $mophTarget = MOPHExportController::getTarget($patient->hn);
            $checkList['mophTargetCheck'] = isset($mophTarget['person']) && (!empty($mophTarget['person']));
            $checkList['morhTargetDetail'] = (isset($mophTarget['person'])) ? $mophTarget['person'] : null;
            $checkList['mophVaccineHistory'] = (isset($mophTarget['vaccine_history'])) ? $mophTarget['vaccine_history'] : [];


            $appointment = self::checkAppointment($patient->hn);
            $checkList['appointmentToday'] = $appointment["appointmentToday"];
            $checkList['appointmentReach'] = $appointment["appointmentReach"];
            $checkList['appointmentDateTime'] = $appointment["appointmentDateTime"];
            $checkList['appointmentSource'] = $appointment["appointmentSource"];
            $checkList['vaccineHistoryCount'] = $appointment["vaccineHistoryCount"];
            $checkList['vaccineSuggestion'] = $appointment["vaccineSuggestion"];
            $checkList['vaccineSuggestionColor'] = $appointment["vaccineSuggestionColor"];
            if ($appointment['isVaccineTooEarly']) $checkList['cautions'][] = 'อาจมาก่อนกำหนด';
        }

        return $checkList;
    }

    public static function checkAppointment($cid,$reachBefore=30) {
        $hospitalCode = (!empty(env('FIELD_HOSPITAL_CODE',''))) ? env('FIELD_HOSPITAL_CODE','') : env('HOSPITAL_CODE', '');
        $hospitalName = (!empty(env('FIELD_HOSPITAL_NAME',''))) ? env('FIELD_HOSPITAL_NAME','') : env('HOSPITAL_NAME', '');

        $appointment = [
            "cid" => $cid,
            "hospitalCode" => $hospitalCode,
            "hospitalName" => $hospitalName,
            "appointmentToday" => false,
            "appointmentReach" => false,
            "appointmentDateTime" => null,
            "appointmentSource" => null,
            "nextAppointmentExist" => false,
            "nextAppointmentDateTime" => null,
            "nextAppointmentSource" => null,
            "patientName" => null,
            "vaccineHistoryCount" => 0,
            "vaccineSuggestion" => null,
            "vaccineSuggestionColor" => null,
            "isVaccineTooEarly" => false,
        ];

        $suggestionCode = null;
        $lastVaccineDate = null;

        //Check MOPH Appointment
        $mophTarget = MOPHExportController::getTarget($cid);
        if (isset($mophTarget['vaccine_history_count'])) $appointment["vaccineHistoryCount"] = $mophTarget['vaccine_history_count'];
        if (isset($mophTarget['person']) && !empty($mophTarget['person'])) $appointment["patientName"] = $mophTarget["person"]["prefix"]." ".$mophTarget["person"]["first_name"]." ".$mophTarget["person"]["last_name"];
        if (isset($mophTarget['confirm_appointment_slot'])) {
            $mophAppointment = collect($mophTarget['confirm_appointment_slot']);
            $mophTodayAppointment = $mophAppointment->where('hospital_code',$hospitalCode)->filter(function($value) {
                                        return \Carbon\Carbon::parse($value["appointment_date"])->isToday();
                                    })->first();
            if ($mophTodayAppointment) {
                $mophTodayAppointment = \Carbon\Carbon::parse($mophTodayAppointment["appointment_date"]." ".$mophTodayAppointment["appointment_time"]);
                $appointment["appointmentToday"] = true;
                $appointment["appointmentReach"] = \Carbon\Carbon::now()->addMinutes($reachBefore)->greaterThanOrEqualTo($mophTodayAppointment);
                $appointment["appointmentDateTime"] = $mophTodayAppointment->format('Y-m-d H:i:s');
                $appointment["appointmentSource"] = "MOPH";
            }
            $mophNextAppointment = $mophAppointment->where('hospital_code',$hospitalCode)->filter(function($value) {
                                        return \Carbon\Carbon::parse($value["appointment_date"])->isFuture();
                                    })->first();
            if ($mophNextAppointment) {
                $mophNextAppointment = \Carbon\Carbon::parse($mophNextAppointment["appointment_date"]." ".$mophNextAppointment["appointment_time"]);
                $appointment["nextAppointmentExist"] = true;
                $appointment["nextAppointmentDateTime"] = $mophNextAppointment->format('Y-m-d H:i:s');
                $appointment["nextAppointmentSource"] = "MOPH";
            }
        }

        //Check self Appointment
        $selfTodayAppointment = \App\Models\Appointment\Appointments::where('hn',$cid)->whereDate('appointmentDateTime',\Carbon\Carbon::now())->orderBy('appointmentDateTime')->first();
        $selfNextAppointment = \App\Models\Appointment\Appointments::where('hn',$cid)->whereDate('appointmentDateTime','>',\Carbon\Carbon::now())->orderBy('appointmentDateTime')->first();

        if (!$appointment["appointmentToday"] && $selfTodayAppointment) {
            $appointment["appointmentToday"] = true;
            $appointment["appointmentReach"] = \Carbon\Carbon::now()->addMinutes($reachBefore)->greaterThanOrEqualTo($selfTodayAppointment->appointmentDateTime);
            $appointment["appointmentDateTime"] = $selfTodayAppointment->appointmentDateTime->format('Y-m-d H:i:s');
            $appointment["appointmentSource"] = "RAMACARE";

            $activity = \App\Models\Master\MasterItems::where('groupKey','$AppointmentActivity')->where('itemCode',$selfTodayAppointment->appointmentActivity)->first();
            if ($activity && isset($activity->properties["productCode"])) $suggestionCode = $activity->properties["productCode"];

            if (!$appointment["patientName"]) $appointment["patientName"] = $selfTodayAppointment->patient->name_th->fullname;
        }

        if ($selfNextAppointment && (!$appointment['nextAppointmentExist'] || $selfNextAppointment->appointmentDateTime->isBefore(\Carbon\Carbon::parse($appointment["nextAppointmentDateTime"])))) {
            $appointment["nextAppointmentExist"] = true;
            $appointment["nextAppointmentDateTime"] = $selfNextAppointment->appointmentDateTime->format('Y-m-d H:i:s');
            $appointment["nextAppointmentSource"] = "RAMACARE";

            if (!$appointment["patientName"]) $appointment["patientName"] = $selfNextAppointment->patient->name_th->fullname;
        }

        //Check MOPH Vaccine History
        if (!$appointment["appointmentToday"] && isset($mophTarget['vaccine_history'])) {
            $history = collect($mophTarget['vaccine_history']);
            $historyToday = $history->where('hospital_code',$hospitalCode)->filter(function($value) {
                                $historyAppoint = collect($value["appointment"]);
                                return $historyAppoint->contains(function ($value) {
                                            return \Carbon\Carbon::parse($value["appointment_date"])->isToday();
                                        });
                            })->first();
            if ($historyToday) {
                $historyAppointment = collect($historyToday["appointment"])->where('appointment_date',\Carbon\Carbon::now()->format('Y-m-d'))->first();
                if ($historyAppointment) {
                    $historyAppointment = \Carbon\Carbon::parse($historyAppointment["appointment_date"]." ".$historyAppointment["appointment_time"]);
                    $appointment["appointmentToday"] = true;
                    $appointment["appointmentReach"] = \Carbon\Carbon::now()->addMinutes($reachBefore)->greaterThanOrEqualTo($historyAppointment);
                    $appointment["appointmentDateTime"] = $historyAppointment->format('Y-m-d H:i:s');
                    $appointment["appointmentSource"] = "MOPH-H";

                    $interval = \Carbon\Carbon::parse($historyToday["immunization_datetime"])->timezone(config('app.timezone'))->diffInWeeks($historyAppointment);
                    if ($interval<=8) $suggestionCode = "00000000000000";
                    else $suggestionCode = "05000456068253";
                }
            }
        }

        if (!$appointment['nextAppointmentExist'] && isset($mophTarget['vaccine_history'])) {
            $history = collect($mophTarget['vaccine_history']);
            $historyNext = $history->where('hospital_code',$hospitalCode)->filter(function($value) {
                                $historyAppoint = collect($value["appointment"]);
                                return $historyAppoint->contains(function ($value) {
                                            return \Carbon\Carbon::parse($value["appointment_date"])->isFuture();
                                        });
                            })->first();
            if ($historyNext) {
                \Log::info('found');
                $historyNextAppointment = collect($historyNext["appointment"])->filter(function ($value) {
                                            return \Carbon\Carbon::parse($value["appointment_date"])->isFuture();
                                        })->first();
                if ($historyNextAppointment) {
                    $historyNextAppointment = \Carbon\Carbon::parse($historyNextAppointment["appointment_date"]." ".$historyNextAppointment["appointment_time"]);
                    $appointment["nextAppointmentExist"] = true;
                    $appointment["nextAppointmentDateTime"] = $historyNextAppointment->format('Y-m-d H:i:s');
                    $appointment["nextAppointmentSource"] = "MOPH-H";
                }
            }
        }

        //Check MOPH Addons
        $addonAppointment = \App\Models\Moph\MophAddons::where('cid',$cid)->whereDate('appointmentDateTime',\Carbon\Carbon::now())->orderBy('appointmentDateTime')->first();
        if (!$appointment["appointmentToday"] && $addonAppointment) {
            $appointment["appointmentToday"] = true;
            $appointment["appointmentReach"] = \Carbon\Carbon::now()->addMinutes($reachBefore)->greaterThanOrEqualTo($addonAppointment->appointmentDateTime);
            $appointment["appointmentDateTime"] = $addonAppointment->appointmentDateTime->format('Y-m-d H:i:s');
            $appointment["appointmentSource"] = ($addonAppointment->group) ?: "GROUP";

            if (!empty($addonAppointment->vaccine)) {
                $suggestion = \App\Models\Master\MasterItems::where('groupKey','covid19VaccineSuggestion')->where('itemCode',$addonAppointment->vaccine)->first();
                if ($suggestion && isset($suggestion->properties["productCode"])) $suggestionCode = $suggestion->properties["productCode"];
            }
            
            if (!$appointment["patientName"]) $appointment["patientName"] = $addonAppointment->name;
        }

        $addonNextAppointment = \App\Models\Moph\MophAddons::where('cid',$cid)->whereDate('appointmentDateTime','>',\Carbon\Carbon::now())->orderBy('appointmentDateTime')->first();
        if ($addonNextAppointment && (!$appointment['nextAppointmentExist'] || $addonNextAppointment->appointmentDateTime->isBefore(\Carbon\Carbon::parse($appointment["nextAppointmentDateTime"])))) {
            $appointment["nextAppointmentExist"] = true;
            $appointment["nextAppointmentDateTime"] = $addonNextAppointment->appointmentDateTime->format('Y-m-d H:i:s');
            $appointment["nextAppointmentSource"] = ($addonNextAppointment->group) ?: "GROUP";

            if (!$appointment["patientName"]) $appointment["patientName"] = $addonNextAppointment->name;
        }

        //Check VIP
        $isVip = \App\Models\Moph\MophVips::find($cid);
        if ($isVip) {
            $appointment["appointmentToday"] = true;
            $appointment["appointmentReach"] = true;
            $appointment["appointmentDateTime"] = \Carbon\Carbon::now()->startOfHour()->format('Y-m-d H:i:s');
            $appointment["appointmentSource"] = "MOPH";

            if (!empty($isVip->vaccine) && empty($suggestionCode)) {
                $suggestion = \App\Models\Master\MasterItems::where('groupKey','covid19VaccineSuggestion')->where('itemCode',$isVip->vaccine)->first();
                if ($suggestion && isset($suggestion->properties["productCode"])) $suggestionCode = $suggestion->properties["productCode"];
            }

            if (!$appointment["patientName"]) $appointment["patientName"] = $isVip->name;
        }

        //Check External Appointment
        if (!$appointment["appointmentToday"]) {
            $externalAppointment = self::checkExternalAppointment($cid);
            if ($externalAppointment) {
                if ($externalAppointment["appointmentToday"]) {
                    $appointment["appointmentToday"] = $externalAppointment["appointmentToday"];
                    $appointment["appointmentReach"] = $externalAppointment["appointmentReach"];
                    $appointment["appointmentDateTime"] = $externalAppointment["appointmentDateTime"];
                    $appointment["appointmentSource"] = $externalAppointment["appointmentSource"];
                }
                if ($externalAppointment["nextAppointmentExist"]) {
                    $appointment["nextAppointmentExist"] = $externalAppointment["nextAppointmentExist"];
                    $appointment["nextAppointmentDateTime"] = $externalAppointment["nextAppointmentDateTime"];
                    $appointment["nextAppointmentSource"] = $externalAppointment["nextAppointmentSource"];
                }
                if ($externalAppointment["mophVaccineHistoryCount"] > $appointment["vaccineHistoryCount"]) $appointment["vaccineHistoryCount"] = $externalAppointment["mophVaccineHistoryCount"];
            }
        }

        //Get rama vaccine count and give suggestion
        $ramaVaccineHistory = \App\Models\Document\Documents::where('hn',$cid)->where('templateCode','cv19-vaccine-administration')->where('status','approved')->orderBy('id')->get();

        if ($ramaVaccineHistory->count() > 0) {
            if (isset($ramaVaccineHistory->first()->data["productCode"])) $suggestionCode = $ramaVaccineHistory->first()->data["productCode"];
            if ($ramaVaccineHistory->count()>$appointment["vaccineHistoryCount"]) $appointment["vaccineHistoryCount"] = $ramaVaccineHistory->count();
            $lastVaccineDate = $ramaVaccineHistory->max('created_at');
        }

        if (empty($suggestionCode) && $appointment["appointmentSource"]) {
            $suggestion = \App\Models\Master\MasterItems::where('groupKey','covid19VaccineSuggestion')->where('itemCode',$appointment["appointmentSource"])->first();
            if ($suggestion && isset($suggestion->properties["productCode"])) $suggestionCode = $suggestion->properties["productCode"];
        }

        if (empty($suggestionCode) && $appointment["appointmentToday"]) {
            $suggestion = \App\Models\Master\MasterItems::where('groupKey','covid19VaccineSuggestion')->where('itemCode',"Default")->first();
            if ($suggestion && isset($suggestion->properties["productCode"])) $suggestionCode = $suggestion->properties["productCode"];
        }

        if (!empty($suggestionCode)) {
            $vaccine = \App\Models\Master\MasterItems::where('groupKey','covid19Vaccine')->where('itemCode',$suggestionCode)->first();
            $appointment['vaccineSuggestion'] = $vaccine->itemValue;
            $appointment['vaccineSuggestionColor'] = $vaccine->properties["color"];

            if ($appointment["vaccineHistoryCount"]>=1) {
                if (isset($mophTarget['vaccine_history'])) {
                    $targetLastVaccineDate = collect($mophTarget['vaccine_history'])->map(function($item) { return \Carbon\Carbon::parse($item["immunization_datetime"])->timezone(config('app.timezone'));})->max();
                    if (empty($lastVaccineDate) || $lastVaccineDate->isBefore($targetLastVaccineDate)) $lastVaccineDate = $targetLastVaccineDate;
                }
                if ($lastVaccineDate && isset($vaccine->properties["vaccine_interval"])) {
                    if (\Carbon\Carbon::now()->diffInWeeks($lastVaccineDate) < $vaccine->properties["vaccine_interval"]-1) $appointment['isVaccineTooEarly'] = true;
                }
            }
        }

        return $appointment;
    }

    public static function checkExternalAppointment($cid) {
        if ((bool)env('EXTERNAL_APPOINTMENT', false)) {
            $ApiMethod = "POST";
            $ApiUrl = (config('app.env')=="PROD") ? 'https://care.rama.mahidol.ac.th/api/public/Covid19/Covid19VaccineController/checkAppointmentPid' : 'https://testcare.rama.mahidol.ac.th/api/public/Covid19/Covid19VaccineController/checkAppointmentPid';

            $requestData = [
                'headers' => [
                  'Accept' => 'application/json',
                  'Content-Type' => 'application/json',
                ],
                'verify' => false
              ];

            $CallData = [
                "pid" => $cid
            ];

            $requestData['json'] = $CallData;
            $requestData['timeout'] = 5;

            try {
                $client = new \GuzzleHttp\Client();
                $res = $client->request($ApiMethod,$ApiUrl,$requestData);

                $httpResponseCode = $res->getStatusCode();
                $httpResponseReason = $res->getReasonPhrase();

                if ($httpResponseCode==200) {
                    $ApiData = json_decode((String)$res->getBody(),true);

                    if ($ApiData["success"]) {
                        return $ApiData["data"];
                    } else {
                        return null;
                    }
                } else {
                    return null;
                }
            } catch (\GuzzleHttp\Exception\RequestException $e) {
              log::error("Error calling to $ApiMethod $ApiUrl.",["Message"=>$e->getMessage(),"RequestData"=>$requestData]);

              return null;
            }
        } else {
            return null;
        }
    }

    public static function autoDischarge($adminForm) {
        if ($adminForm->status=='approved') {
            $template = \App\Models\Document\DocumentsTemplates::find('cv19-vaccine-discharge');

            $dischargeFormExist = \App\Models\Document\Documents::where('hn',$adminForm->hn)->where('templateCode','cv19-vaccine-discharge')
                    ->where('created_at','>=',$adminForm->created_at)
                    ->where('status','approved')
                    ->exists();

            if (!$dischargeFormExist && $template) {
                $vaccinePassport = \App\Models\Document\Documents::where('hn',$adminForm->hn)->where('templateCode','cv19-vaccine-passport')
                            ->where('created_at','>=',$adminForm->created_at)
                            ->where('status','approved')
                            ->orderBy('created_at','desc')
                            ->first();
                $createUser = ($vaccinePassport) ? $vaccinePassport->created_by : $adminForm->created_by;
                $dischargeForm = new \App\Models\Document\Documents();
                $dischargeForm->hn = $adminForm->hn;
                $dischargeForm->templateCode = 'cv19-vaccine-discharge';
                $dischargeForm->folder = $adminForm->folder;
                $dischargeForm->category = $template->defaultCategory;
                $dischargeForm->data = ["isSymptomAfterVaccine"=>false];
                $dischargeForm->created_by = $createUser;
                $dischargeForm->status = 'approved';
                $dischargeForm->save();

                Log::info('Generate cv19-vaccine-discharge document id - '.$dischargeForm->id,$dischargeForm->toArray());
            }

            $adminCount = \App\Models\Document\Documents::where('hn',$adminForm->hn)->where('templateCode','cv19-vaccine-administration')
                    ->where('status','approved')
                    ->count();

            $adminFirst = \App\Models\Document\Documents::where('hn',$adminForm->hn)->where('templateCode','cv19-vaccine-administration')
                    ->where('status','approved')
                    ->orderBy('created_at','asc')
                    ->first();

            $previousVisitCount = 0;
            $mophHistory = MOPHExportController::getHistory($adminForm->hn);
            if (isset($mophHistory["patient"]) && isset($mophHistory["patient"]["visit"])) {
                $history = collect($mophHistory["patient"]["visit"]);
                $previousVisit = $history->filter(function($value) use ($adminFirst) {
                                            return (isset($value["visit_immunization"]) && isset($value["visit_immunization"][0]["immunization_datetime"])) && \Carbon\Carbon::parse($value["visit_immunization"][0]["immunization_datetime"])->timezone(config('app.timezone'))->endOfDay()->isBefore((isset($adminFirst)) ? $adminFirst->created_at : \Carbon\Carbon::now());
                                        });
                $previousVisitCount = $previousVisit->count();                   
            }

            if ($adminForm && isset($adminForm->data["productCode"])) {
                $productCode = $adminForm->data["productCode"];
                $vaccine = \App\Models\Master\MasterItems::where('groupKey','covid19Vaccine')->where('itemCode',$productCode)->first();
                if ($vaccine) {
                    $appointActivityPrefix = (isset($vaccine->properties["appointment_activity_prefix"])) ? $vaccine->properties["appointment_activity_prefix"] : '9';
                    $totalDose = (isset($vaccine->properties["vaccine_total_dose"]) && !empty($vaccine->properties["vaccine_total_dose"])) ? $vaccine->properties["vaccine_total_dose"] : 2;
                    $vaccineInterval = (isset($vaccine->properties["vaccine_interval"]) && !empty($vaccine->properties["vaccine_interval"])) ? $vaccine->properties["vaccine_interval"] : 4;

                    if (($adminCount + $previousVisitCount)<$totalDose) {
                        $appointment = \App\Models\Appointment\Appointments::where('hn',$adminForm->hn)
                                ->whereDate('appointmentDateTime','>=',$adminForm->created_at->addWeeks($vaccineInterval-1))
                                ->whereDate('appointmentDateTime','<=',$adminForm->created_at->addWeeks($vaccineInterval+1))
                                ->first();

                        if (!$appointment) {
                            $appointment = new \App\Models\Appointment\Appointments();
                            $appointment->hn = $adminForm->hn;
                            $appointment->clinicCode = 'VACCINE';
                            $appointment->doctorCode = 'CN01';
                            $appointment->appointmentType = 'VACCINE';
                            $appointment->appointmentActivity = $appointActivityPrefix.str_pad($adminCount+1,2,"0",STR_PAD_LEFT);
                            $appointment->appointmentDateTime = $adminForm->created_at->addWeeks($vaccineInterval)->startOfHour();
                            $appointment->save();
                        }
                    }
                }
            }
        }
    }

    public static function autoAppointment() {
        $adminForms = \App\Models\Document\Documents::where('templateCode','cv19-vaccine-administration')
                    ->where('status','approved')
                    ->orderBy('created_at','asc')
                    ->get();

        foreach($adminForms as $adminForm) {
            $adminCount = \App\Models\Document\Documents::where('hn',$adminForm->hn)->where('templateCode','cv19-vaccine-administration')
                            ->where('status','approved')
                            ->count();
            $adminFirst = \App\Models\Document\Documents::where('hn',$adminForm->hn)->where('templateCode','cv19-vaccine-administration')
                            ->where('status','approved')
                            ->orderBy('created_at','asc')
                            ->first();

            $previousVisitCount = 0;
            $mophHistory = MOPHExportController::getHistory($adminForm->hn);
            if (isset($mophHistory["patient"]) && isset($mophHistory["patient"]["visit"])) {
                $history = collect($mophHistory["patient"]["visit"]);
                $previousVisit = $history->filter(function($value) use ($adminFirst) {
                                            return (isset($value["visit_immunization"]) && isset($value["visit_immunization"][0]["immunization_datetime"])) && \Carbon\Carbon::parse($value["visit_immunization"][0]["immunization_datetime"])->timezone(config('app.timezone'))->endOfDay()->isBefore((isset($adminFirst)) ? $adminFirst->created_at : \Carbon\Carbon::now());
                                        });
                $previousVisitCount = $previousVisit->count();                   
            }

            if ($adminForm && isset($adminForm->data["productCode"])) {
                $productCode = $adminForm->data["productCode"];
                $vaccine = \App\Models\Master\MasterItems::where('groupKey','covid19Vaccine')->where('itemCode',$productCode)->first();
                if ($vaccine) {
                    $appointActivityPrefix = (isset($vaccine->properties["appointment_activity_prefix"])) ? $vaccine->properties["appointment_activity_prefix"] : '9';
                    $totalDose = (isset($vaccine->properties["vaccine_total_dose"]) && !empty($vaccine->properties["vaccine_total_dose"])) ? $vaccine->properties["vaccine_total_dose"] : 2;
                    $vaccineInterval = (isset($vaccine->properties["vaccine_interval"]) && !empty($vaccine->properties["vaccine_interval"])) ? $vaccine->properties["vaccine_interval"] : 4;

                    if (($adminCount + $previousVisitCount)<$totalDose) {
                        $appointment = \App\Models\Appointment\Appointments::where('hn',$adminForm->hn)
                                ->whereDate('appointmentDateTime','>=',$adminForm->created_at->addWeeks(round($vaccineInterval-1)))
                                ->whereDate('appointmentDateTime','<=',$adminForm->created_at->addWeeks(round($vaccineInterval+1)))
                                ->first();

                        if (!$appointment) {
                            $appointment = new \App\Models\Appointment\Appointments();
                            $appointment->hn = $adminForm->hn;
                            $appointment->clinicCode = 'VACCINE';
                            $appointment->doctorCode = 'CN01';
                            $appointment->appointmentType = 'VACCINE';
                            $appointment->appointmentActivity = $appointActivityPrefix.str_pad($adminCount+1,2,"0",STR_PAD_LEFT);
                            $appointment->appointmentDateTime = $adminForm->created_at->addDays(round($vaccineInterval*7))->startOfHour();
                            $appointment->save();
                        }
                    }
                }
            }
        }
    }

    public static function cleanupAppointment() {
        $activity = \App\Models\Master\MasterItems::where('groupKey','$AppointmentActivity')->get();
        $suggestion = \App\Models\Master\MasterItems::where('groupKey','covid19VaccineSuggestion')->get();
        $vaccineData = \App\Models\Master\MasterItems::where('groupKey','covid19Vaccine')->get();

        $appointments = \App\Models\Appointment\Appointments::whereDate('appointmentDateTime','>=',\Carbon\Carbon::now())->get();
        foreach($appointments as $appointment) {
            $tmpActivity = $activity->firstWhere('itemCode',$appointment->appointmentActivity);
            $vaccine = ($tmpActivity) ? $vaccineData->firstWhere('itemCode',$tmpActivity->properties["productCode"]) : null;
            $totalDose = (isset($vaccine->properties["vaccine_total_dose"]) && !empty($vaccine->properties["vaccine_total_dose"])) ? $vaccine->properties["vaccine_total_dose"] : 2;
            
            try {
                $previousVisitCount = 0;
                $mophTarget = MOPHExportController::getTarget($appointment->hn);
                if (isset($mophTarget['vaccine_history_count'])) $previousVisitCount = $mophTarget['vaccine_history_count'];
            } catch(\Exception $e) {
                \Log::error('Clean up error '.$appointment->hn,["Message"=>$e->getMessage()]);
            }

            if ($previousVisitCount>=$totalDose) $appointment->delete();
        }
    }

    public static function generateVaccinePassport($hn) {
        $adminForms = \App\Models\Document\Documents::where('hn',$hn)->where('templateCode','cv19-vaccine-administration')
                    ->where('status','approved')
                    ->orderBy('created_at','asc')
                    ->get();

        $adminForm = null;
        $productCode = null;

        $template = \App\Models\Document\DocumentsTemplates::find('cv19-vaccine-passport');

        $passportData = [];

        //get dose from MOPH
        $previousVisitCount = 0;
        $previousVisit = collect();
        try {
            $mophHistory = MOPHExportController::getHistory($hn);
            if (isset($mophHistory["patient"]) && isset($mophHistory["patient"]["visit"])) {
                $history = collect($mophHistory["patient"]["visit"]);
                $previousVisit = $history->filter(function($value) use ($adminForms) {
                                            return (isset($value["visit_immunization"]) && isset($value["visit_immunization"][0]["immunization_datetime"])) && \Carbon\Carbon::parse($value["visit_immunization"][0]["immunization_datetime"])->timezone(config('app.timezone'))->endOfDay()->isBefore((isset($adminForms[0])) ? $adminForms[0]->created_at : \Carbon\Carbon::now());
                                        });
                $previousVisitCount = $previousVisit->count();                   
            }

            for($i=1;$i<=$previousVisitCount;$i++) {
                $passportData = array_merge($passportData,[
                    'productCode'.$i=>$previousVisit[$i-1]["visit_immunization"][0]["vaccine_name"],
                    'lotNo'.$i=>$previousVisit[$i-1]["visit_immunization"][0]["lot_number"],
                    'serialNo'.$i=>$previousVisit[$i-1]["visit_immunization"][0]["vaccine_serial_no"],
                    'expDate'.$i=>$previousVisit[$i-1]["visit_immunization"][0]["vaccine_expiration_date"],
                    'adminDateTime'.$i=>\Carbon\Carbon::parse($previousVisit[$i-1]["visit_immunization"][0]["immunization_datetime"])->toDateTimeString(),
                    'dischargeDateTime'.$i=>\Carbon\Carbon::parse($previousVisit[$i-1]["visit_immunization"][0]["immunization_datetime"])->addMinutes(30)->toDateTimeString(),
                    'adminRoute'.$i=>null,
                    'adminSite'.$i=>null,
                    'adminSiteOther'.$i=>null,
                    'created_by'.$i=>$previousVisit[$i-1]["visit_immunization"][0]["practitioner_name"],
                ]);
            }
        } catch(\Exception $e) {
            $previousVisitCount = 0;
            $passportData = [];
        }

        for($i=1;$i<=3;$i++) {
            if ($adminForms && isset($adminForms[$i-1])) {
                $passportData = array_merge($passportData,[
                    'productCode'.($previousVisitCount+$i)=> isset($adminForms[$i-1]->data["productCode"]) ? $adminForms[$i-1]->data["productCode"] : null,
                    'lotNo'.($previousVisitCount+$i)=> isset($adminForms[$i-1]->data["lotNo"]) ? $adminForms[$i-1]->data["lotNo"] : null,
                    'serialNo'.($previousVisitCount+$i)=> isset($adminForms[$i-1]->data["serialNo"]) ? $adminForms[$i-1]->data["serialNo"] : null,
                    'expDate'.($previousVisitCount+$i)=> isset($adminForms[$i-1]->data["expDate"]) ? $adminForms[$i-1]->data["expDate"] : null,
                    'adminDateTime'.($previousVisitCount+$i)=> isset($adminForms[$i-1]->data["adminDateTime"]) ? \Carbon\Carbon::parse($adminForms[$i-1]->data["adminDateTime"])->timezone(config('app.timezone'))->toDateTimeString() : null,
                    'dischargeDateTime'.($previousVisitCount+$i)=> isset($adminForms[$i-1]->data["adminDateTime"]) ? \Carbon\Carbon::parse($adminForms[$i-1]->data["adminDateTime"])->timezone(config('app.timezone'))->addMinutes(30)->toDateTimeString() : null,
                    'adminRoute'.($previousVisitCount+$i)=> isset($adminForms[$i-1]->data["adminRoute"]) ? $adminForms[$i-1]->data["adminRoute"] : null,
                    'adminSite'.($previousVisitCount+$i)=> isset($adminForms[$i-1]->data["adminSite"]) ? $adminForms[$i-1]->data["adminSite"] : null,
                    'adminSiteOther'.($previousVisitCount+$i)=> isset($adminForms[$i-1]->data["adminSiteOther"]) ? $adminForms[$i-1]->data["adminSiteOther"] : null,
                    'created_by'.($previousVisitCount+$i)=>$adminForms[$i-1]->created_by,
                ]);
                $productCode = isset($adminForms[$i-1]->data["productCode"]) ? $adminForms[$i-1]->data["productCode"] : null;
                $adminForm = $adminForms[$i-1];
            } else {
                $passportData = array_merge($passportData,[
                    'productCode'.($previousVisitCount+$i)=>null,
                    'lotNo'.($previousVisitCount+$i)=>null,
                    'serialNo'.($previousVisitCount+$i)=>null,
                    'expDate'.($previousVisitCount+$i)=>null,
                    'adminDateTime'.($previousVisitCount+$i)=>null,
                    'dischargeDateTime'.($previousVisitCount+$i)=>null,
                    'adminRoute'.($previousVisitCount+$i)=>null,
                    'adminSite'.($previousVisitCount+$i)=>null,
                    'adminSiteOther'.($previousVisitCount+$i)=>null,
                    'created_by'.($previousVisitCount+$i)=>null,
                ]);
            }
        }
        
        //Generating or get appointment
        if ($productCode) {
            $vaccine = \App\Models\Master\MasterItems::where('groupKey','covid19Vaccine')->where('itemCode',$productCode)->first();
            if ($vaccine) {
                $adminCount = count($adminForms) + $previousVisitCount;

                $appointActivityPrefix = (isset($vaccine->properties["appointment_activity_prefix"])) ? $vaccine->properties["appointment_activity_prefix"] : '9';
                $totalDose = (isset($vaccine->properties["vaccine_total_dose"]) && !empty($vaccine->properties["vaccine_total_dose"])) ? $vaccine->properties["vaccine_total_dose"] : 2;
                $vaccineInterval = (isset($vaccine->properties["vaccine_interval"]) && !empty($vaccine->properties["vaccine_interval"])) ? $vaccine->properties["vaccine_interval"] : 4;

                if ($adminCount<$totalDose) {
                    $appointment = \App\Models\Appointment\Appointments::where('hn',$hn)
                            ->whereDate('appointmentDateTime','>=',$adminForm->created_at->addWeeks(round($vaccineInterval-1)))
                            ->whereDate('appointmentDateTime','<=',$adminForm->created_at->addWeeks(round($vaccineInterval+1)))
                            ->first();

                    if (!$appointment) {
                        $appointment = new \App\Models\Appointment\Appointments();
                        $appointment->hn = $hn;
                        $appointment->clinicCode = 'VACCINE';
                        $appointment->doctorCode = 'CN01';
                        $appointment->appointmentType = 'VACCINE';
                        $appointment->appointmentActivity = $appointActivityPrefix.str_pad($adminCount+1,2,"0",STR_PAD_LEFT);
                        $appointment->appointmentDateTime = $adminForm->created_at->addDays(round($vaccineInterval*7))->startOfHour();
                        $appointment->save();
                    }

                    $passportData["appointmentDate"] = $appointment->appointmentDateTime->startOfHour()->toDateTimeString();
                }
            }
        }

        if ($template) {
            $passport = new \App\Models\Document\Documents();
            $passport->hn = $hn;
            $passport->templateCode = 'cv19-vaccine-passport';
            $passport->folder = 'default';
            $passport->category = $template->defaultCategory;
            $passport->data = $passportData;
            $passport->save();

            return $passport;
        } else {
            return ["success" => false, "errorTexts" => [], "returnModels" => []];
        }
    }

    public static function getAdministration($beginDate,$endDate=null) {
        $from = \Carbon\Carbon::parse($beginDate)->timezone(config('app.timezone'))->startOfDay()->toDateTimeString();
        $to = ($endDate) ? \Carbon\Carbon::parse($endDate)->timezone(config('app.timezone'))->endOfDay()->toDateTimeString() : $from;

        $documents = \App\Models\Document\Documents::where('templateCode','cv19-vaccine-administration')
                    ->where('status','approved')
                    ->whereBetween('created_at',[$from,$to])
                    ->get()
                    ->makeHidden(['patient','patient_age','patient_age_en']);

        $documents = $documents->map(function($item,$key) {
            $vaccine = \App\Models\Master\MasterItems::where('groupKey','covid19Vaccine')->where('itemCode',$item->data["productCode"])->first();

            $encounter = explode('#',$item->encounterId);

            return [
                'hn' => $item->hn,
                'encounterType' => (count($encounter)==2) ? $encounter[0] : $item->encounterId,
                'encounterId' => (count($encounter)==2) ? $encounter[1] : $item->encounterId,
                'adminDateTime' => $item->data['adminDateTime'],
                'productCode' => (!empty($vaccine->properties["rama_product_code"])) ? $vaccine->properties["rama_product_code"] : $item->data["productCode"],
                'productName' => $vaccine->itemValue,
                'created_by' => $item->created_by,
                'created_at' => $item->created_at->format('Y-m-d H:i:s')
            ];
        });

        return $documents;
    }

    public static function getTargetToPatient($personId) {
        $returnModels = [];

        $namePrefix = [
            "ด.ช."=>"001",
            "ด.ญ."=>"002",
            "นาย"=>"003",
            "น.ส."=>"004",
            "นาง"=>"005",
            "นางสาว"=>"004",
            "Master"=>"001",
            "Miss"=>"002",
        ];

        $mophTarget = MOPHExportController::getTarget($personId);
        if ($mophTarget && isset($mophTarget['person']) && !empty($mophTarget['person'])) {
            $returnModels = [
                "patient"=>[
                    "personId"=>$personId,
                    "personIdType"=>1,
                    "dateOfBirth"=>$mophTarget["person"]["birth_date"],
                    "sex"=>$mophTarget["person"]["gender"],
                    "primaryMobileNo"=>$mophTarget["person"]["mobile_phone"]
                ],
                "name"=>[
                    [
                        "namePrefix"=>$namePrefix[$mophTarget["person"]["prefix"]],
                        "nameType"=>"TH",
                        "firstName"=>$mophTarget["person"]["first_name"],
                        "middleName"=>null,
                        "lastName"=>$mophTarget["person"]["last_name"],
                    ]
                ],
                "address"=>[
                    [
                        "addressType"=>"census",
                        "address"=>(isset($mophTarget["person"]["Address"])) ? $mophTarget["person"]["Address"] : "",
                        "moo"=>(isset($mophTarget["person"]["address_moo"])) ? $mophTarget["person"]["address_moo"] : "",
                        "street"=>(isset($mophTarget["person"]["address_road"])) ? $mophTarget["person"]["address_road"] : "",
                        "subdistrict"=>null,
                        "district"=>(isset($mophTarget["person"]["province_code"]) && isset($mophTarget["person"]["district_code"])) ? $mophTarget["person"]["province_code"].$mophTarget["person"]["district_code"] : "",
                        "province"=>(isset($mophTarget["person"]["province_code"])) ? $mophTarget["person"]["province_code"] : "",                  
                    ]
                ]
            ];
        }

        return $returnModels;
    }

    public static function fixVaccineData($documents,$gs1code=null,$productCode=null,$lotNo=null,$serialNo=null,$expDate=null) {
        foreach($documents as $document) {
            $documentData = $document->data;
            if ($gs1code) {
                $documentData["gs1code"] = $gs1code;
            }
            if ($productCode) {
                $documentData["productCode"] = $productCode;
                if (isset($documentData["gs1data"]) && isset($documentData["gs1data"]["productCode"])) $documentData["gs1data"]["productCode"] = $productCode;
            }
            if ($lotNo) {
                $documentData["lotNo"] = $lotNo;
                if (isset($documentData["gs1data"]) && isset($documentData["gs1data"]["lotNo"])) $documentData["gs1data"]["lotNo"] = $lotNo;
            }
            if ($serialNo) {
                $documentData["serialNo"] = $serialNo;
                if (isset($documentData["gs1data"]) && isset($documentData["gs1data"]["serialNo"])) $documentData["gs1data"]["serialNo"] = $serialNo;
            }
            if ($expDate) {
                $documentData["expDate"] = $expDate;
                if (isset($documentData["gs1data"]) && isset($documentData["gs1data"]["expDate"])) $documentData["gs1data"]["expDate"] = $expDate;
            }
            $document->data = $documentData;
            $document->save();
        }
    }

    public static function mophVipsCleanup() {
        $vips = \App\Models\Moph\MophVips::all();
        foreach($vips as $vip) {
            if (\App\Models\Document\Documents::where('hn',$vip->cid)->where('templateCode','cv19-vaccine-administration')->where('status','approved')->exists()) {
                $vip->delete();
            } else {
                $mophTarget = MOPHExportController::getTarget($vip->cid);
                if (isset($mophTarget['vaccine_history_count']) && $mophTarget['vaccine_history_count']>=1) $vip->delete();
            }
        }
    }

    public static function getAppointments($beginDate=null,$endDate=null) {
        if ($beginDate) $beginDate = \Carbon\Carbon::parse($beginDate)->timezone(config('app.timezone'));
        else $beginDate = \Carbon\Carbon::now();

        if ($endDate) $endDate = \Carbon\Carbon::parse($endDate)->timezone(config('app.timezone'));
        else $endDate = $beginDate;

        if ($endDate->isBefore($beginDate)) $endDate = $beginDate;

        $interval = $beginDate->diffInDays($endDate);

        $report = [];

        $activity = \App\Models\Master\MasterItems::where('groupKey','$AppointmentActivity')->get();
        $suggestion = \App\Models\Master\MasterItems::where('groupKey','covid19VaccineSuggestion')->get();
        $vaccineData = \App\Models\Master\MasterItems::where('groupKey','covid19Vaccine')->get();

        for($i=0;$i<=$interval;$i++) {
            $currentDate = $beginDate->copy()->addDays($i);

            $appointments = \App\Models\Appointment\Appointments::whereDate('appointmentDateTime',$currentDate)->distinct('hn')->get();
            $mophAppointments = collect(MOPHExportController::getAppointments($currentDate));
            $groupAppointments = \App\Models\Moph\MophAddons::whereDate('appointmentDateTime',$currentDate)->distinct('cid')->get();

            $appointments = $appointments->toBase()->map(function($item) use ($activity,$suggestion,$vaccineData) {
                $tmpActivity = $activity->firstWhere('itemCode',$item->appointmentActivity);
                $vaccine = ($tmpActivity) ? $vaccineData->firstWhere('itemCode',$tmpActivity->properties["productCode"]) : null;
                $vaccine = ($vaccine) ? $vaccine->itemValue : "unspecified";

                return [
                    "cid" => $item->hn,
                    "group" =>  ($tmpActivity) ? $tmpActivity->itemValue : "unspecified",
                    "vaccine" => $vaccine,
                ];
            });

            $mophAppointments = $mophAppointments->toBase()->map(function($item) use ($activity,$suggestion,$vaccineData) {
                $tmpSuggestion = $suggestion->firstWhere('itemCode','MOPH');
                $vaccine = ($tmpSuggestion) ? $vaccineData->firstWhere('itemCode',$tmpSuggestion->properties["productCode"]) : null;
                $vaccine = ($vaccine) ? $vaccine->itemValue : "unspecified";

                return [
                    "cid" => $item["cid"],
                    "group" => 'MOPH',
                    "vaccine" => $vaccine,
                ];
            });

            $groupAppointments = $groupAppointments->toBase()->map(function($item) use ($activity,$suggestion,$vaccineData) {
                $tmpSuggestion = $suggestion->firstWhere('itemCode',$item->vaccine);
                if ($item->group=="ไทยร่วมใจ") $item->group = "MOPH";

                $vaccine = ($tmpSuggestion) ? $vaccineData->firstWhere('itemCode',$tmpSuggestion->properties["productCode"]) : null;
                $vaccine = ($vaccine) ? $vaccine->itemValue : "unspecified";

                return [
                    "cid" => $item->cid,
                    "group" => ($item->group) ?: "unspecified",
                    "vaccine" => $vaccine,
                ];
            });

            $appointments = $appointments->merge($mophAppointments)->merge($groupAppointments)->unique('cid')->values();

            $subreport = [
                "date" => $currentDate->format('Y-m-d'),
                "total_appointed" => $appointments->count(),
                "group" => $appointments->countBy('group')->toArray(),
                "vaccine" => $appointments->countBy('vaccine')->toArray(),
            ];

            $report[] = $subreport;
        }

        return $report;
    }

    public static function getStatistics($beginDate=null,$endDate=null) {
        if ($beginDate) $beginDate = \Carbon\Carbon::parse($beginDate)->timezone(config('app.timezone'));
        else $beginDate = \Carbon\Carbon::now();

        if ($endDate) $endDate = \Carbon\Carbon::parse($endDate)->timezone(config('app.timezone'));
        else $endDate = $beginDate;

        if ($endDate->isBefore($beginDate)) $endDate = $beginDate;

        $interval = $beginDate->diffInDays($endDate);
        $mophAppointments = collect();
        for($i=0;$i<=$interval;$i++) {
            $currentDate = $beginDate->copy()->addDays($i);
            $mophAppointments = $mophAppointments->merge(MOPHExportController::getAppointments($currentDate));
        }
        $mophAppointments = $mophAppointments->unique('cid')->values();

        $vaccineData = \App\Models\Master\MasterItems::where('groupKey','covid19Vaccine')->get();
        $groupAppointments = \App\Models\Moph\MophAddons::whereDate('appointmentDateTime','>=',$beginDate)->whereDate('appointmentDateTime','<=',$endDate)->distinct('cid')->get();

        $administrations = \App\Models\Document\Documents::where('templateCode','cv19-vaccine-administration')->where('status','approved')->whereDate('created_at','>=',$beginDate)->whereDate('created_at','<=',$endDate)->get();

        $administrations = $administrations->toBase()->map(function($administration) use ($vaccineData,$mophAppointments,$groupAppointments ) {
            $result = [
                "hn" => $administration->hn,
                "dose" => 1,
                "vaccine" => null,
                "group" => null,
            ];

            //get group from group appointment
            if ($groupAppointments->contains('cid',$administration->hn)) {
                $result["group"] = $groupAppointments->firstWhere('cid',$administration->hn)->group;
                if (empty($result["group"])) $result["group"] = "นัดหมายกลุ่มอื่นๆ";
            }

            //get group from moph slot
            if ($mophAppointments->contains('cid',$administration->hn)) {
                $result["group"] = "MOPH";
            }

            //get dose from MOPH
            $previousVisitCount = 0;
            try {
                $mophTarget = MOPHExportController::getTarget($administration->hn);
                if (isset($mophTarget["vaccine_history"])) {

                        $history = collect($mophTarget["vaccine_history"]);
                        $previousVisitCount = $history->filter(function($value) use ($administration) {
                                                    return \Carbon\Carbon::parse($value["immunization_datetime"])->timezone(config('app.timezone'))->endOfDay()->isBefore($administration->created_at);
                                                })->count();
                    
                }
            } catch(\Exception $e) {
                $previousVisitCount = 0;
            }
            $result["dose"] = $previousVisitCount+1;

            $vaccine = $vaccineData->firstWhere('itemCode',$administration->data["productCode"]);
            if ($vaccine) {
                $result["vaccine"] = $vaccine->itemValue;
            }
            
            if ($result["group"]=="ไทยร่วมใจ") $result["group"] = "MOPH";

            if (empty($result["group"])) {
                if ($result["dose"]>1) $result["group"] = "นัดหมายรับเข็ม 2";
                else $result["group"] = "นอกนัดหมาย";
            }

            if (empty($result["vaccine"])) $result["vaccine"] = "unspecified";

            return $result;
        });

        $report = [
            "beginDate" => $beginDate->format('Y-m-d'),
            "endDate" => $endDate->format('Y-m-d'),
            "hospital_code" => (!empty(env('FIELD_HOSPITAL_CODE',''))) ? env('FIELD_HOSPITAL_CODE','') : env('HOSPITAL_CODE', ''),
            "hospital_name" => (!empty(env('FIELD_HOSPITAL_NAME',''))) ? env('FIELD_HOSPITAL_NAME','') : env('HOSPITAL_NAME', ''),
            "total" => $administrations->count(),
            "total_dose_1" => $administrations->where("dose",1)->count(),
            "total_dose_2" => $administrations->where("dose",2)->count(),
        ];

        $grouped = $administrations->groupBy('group');
        if (!$grouped->has("MOPH")) $grouped = $grouped->merge(["MOPH"=>collect([])]);
        if (!$grouped->has("นัดหมายรับเข็ม 2")) $grouped = $grouped->merge(["นัดหมายรับเข็ม 2"=>collect([])]);
        if (!$grouped->has("นัดหมายกลุ่มอื่นๆ")) $grouped = $grouped->merge(["นัดหมายกลุ่มอื่นๆ"=>collect([])]);
        $groupAppointments->pluck('group')->each(function($item) use (&$grouped) {
           if (!$grouped->has($item) && $item!="ไทยร่วมใจ" && $item) $grouped = $grouped->merge([$item=>collect([])]);
        });
        $grouped = $grouped->map(function($group,$key) use ($beginDate,$endDate,$mophAppointments,$groupAppointments) {
            $group = collect($group);
            $result =  [
                "appointed" => 0,
                "visited" => $group->count(),
                "dose_1" => $group->where('dose',1)->count(),
                "dose_2" => $group->where('dose',2)->count(),
            ];

            if ($key=="นัดหมายรับเข็ม 2") $result["appointed"] = \App\Models\Appointment\Appointments::whereDate('appointmentDateTime','>=',$beginDate)->whereDate('appointmentDateTime','<=',$endDate)->distinct('hn')->count();
            else if ($key=="MOPH") $result["appointed"] = $mophAppointments->count();
            else if ($key=="นัดหมายกลุ่มอื่นๆ") $result["appointed"] = $groupAppointments->whereIn('group',["",null])->count();
            else $result["appointed"] = $groupAppointments->where('group',$key)->count();

            $vaccine = $group->countBy('vaccine')->toArray();

            return array_merge($result,$vaccine);
        });

        $report["group"] = $grouped->toArray();
        $report["vaccine"] = $administrations->countBy('vaccine')->toArray();

        return $report;
    }
}
