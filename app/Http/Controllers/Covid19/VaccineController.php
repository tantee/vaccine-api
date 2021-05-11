<?php

namespace App\Http\Controllers\Covid19;

use Illuminate\Http\Request;
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
            "previousVaccine" => null,
            "cautions" => [],
        ];

        $checkList["consentCheck"] = self::checkConsentReady($hn);

        $patient = \App\Models\Patient\Patients::find($hn);
        if($patient) {
            $checkList["peopleIdCheck"] = (isset($patient->hn) && (strlen($patient->hn)==13) && is_numeric($patient->hn));
            $checkList["peopleIdDetail"] = $patient->personId;

            $age = \Carbon\Carbon::now()->diffAsCarbonInterval($patient->dateOfBirth);
            if ($age->y>59 || ($age->y==59 && $age->m>=10)) $checkList["cautions"][] = '> 59 ปี 10 เดือน';

            #$mophTarget = MOPHExportController::getTarget($patient->hn);
            #$checkList['mophTargetCheck'] = isset($mophTarget['person']) && (!empty($mophTarget['person']));
            #$checkList['morhTargetDetail'] = (isset($mophTarget['person'])) ? $mophTarget['person'] : null;
            #$checkList['mophVaccineHistory'] = (isset($mophTarget['vaccine_history'])) ? $mophTarget['vaccine_history'] : [];
        }

        return $checkList;
    }

    public static function autoDischarge($backMinute=90) {
        $adminForms = \App\Models\Document\Documents::where('templateCode','cv19-vaccine-administration')
                    ->whereBetween('created_at',[\Carbon\Carbon::now()->subMinutes($backMinute),\Carbon\Carbon::now()->subMinutes(60)])
                    ->where('status','approved')
                    ->orderBy('created_at','desc')
                    ->get();

        $template = \App\Models\Document\DocumentsTemplates::find('cv19-vaccine-discharge');

        foreach($adminForms as $adminForm) {
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
        }
    }

    public static function generateVaccinePassport($hn) {
        $adminForms = \App\Models\Document\Documents::where('hn',$hn)->where('templateCode','cv19-vaccine-administration')
                    ->where('status','approved')
                    ->orderBy('created_at','asc')
                    ->get();

        $productCode = null;

        $template = \App\Models\Document\DocumentsTemplates::find('cv19-vaccine-passport');

        $passportData = [];

        for($i=1;$i<=2;$i++) {
            if ($adminForms && isset($adminForms[$i-1])) {
                $passportData = array_merge($passportData,[
                    'productCode'.$i=> isset($adminForms[$i-1]->data["productCode"]) ? $adminForms[$i-1]->data["productCode"] : null,
                    'lotNo'.$i=> isset($adminForms[$i-1]->data["lotNo"]) ? $adminForms[$i-1]->data["lotNo"] : null,
                    'serialNo'.$i=> isset($adminForms[$i-1]->data["serialNo"]) ? $adminForms[$i-1]->data["serialNo"] : null,
                    'expDate'.$i=> isset($adminForms[$i-1]->data["expDate"]) ? $adminForms[$i-1]->data["expDate"] : null,
                    'adminDateTime'.$i=> isset($adminForms[$i-1]->data["adminDateTime"]) ? \Carbon\Carbon::parse($adminForms[$i-1]->data["adminDateTime"])->toDateTimeString() : null,
                    'dischargeDateTime'.$i=> isset($adminForms[$i-1]->data["adminDateTime"]) ? \Carbon\Carbon::parse($adminForms[$i-1]->data["adminDateTime"])->addMinutes(30)->toDateTimeString() : null,
                    'adminRoute'.$i=> isset($adminForms[$i-1]->data["adminRoute"]) ? $adminForms[$i-1]->data["adminRoute"] : null,
                    'adminSite'.$i=> isset($adminForms[$i-1]->data["adminSite"]) ? $adminForms[$i-1]->data["adminSite"] : null,
                    'adminSiteOther'.$i=> isset($adminForms[$i-1]->data["adminSiteOther"]) ? $adminForms[$i-1]->data["adminSiteOther"] : null,
                    'created_by'.$i=>$adminForms[$i-1]->created_by,
                ]);
                $productCode = isset($adminForms[$i-1]->data["productCode"]) ? $adminForms[$i-1]->data["productCode"] : null;
            } else {
                $passportData = array_merge($passportData,[
                    'productCode'.$i=>null,
                    'lotNo'.$i=>null,
                    'serialNo'.$i=>null,
                    'expDate'.$i=>null,
                    'adminDateTime'.$i=>null,
                    'dischargeDateTime'.$i=>null,
                    'adminRoute'.$i=>null,
                    'adminSite'.$i=>null,
                    'adminSiteOther'.$i=>null,
                    'created_by'.$i=>null,
                ]);
            }
        }

        if ($productCode) {
            $vaccine = \App\Models\Master\MasterItems::where('groupKey','covid19Vaccine')->where('itemCode',$productCode)->first();
            if ($vaccine) {
                $totalDose = (isset($vaccine->properties["vaccine_total_dose"]) && !empty($vaccine->properties["vaccine_total_dose"])) ? $vaccine->properties["vaccine_total_dose"] : 2;
                $vaccineInterval = (isset($vaccine->properties["vaccine_interval"]) && !empty($vaccine->properties["vaccine_interval"])) ? $vaccine->properties["vaccine_interval"] : 4;

                if (count($adminForms)<$totalDose) {
                    $appointment = \App\Models\Appointment\Appointments::where('hn',$hn)
                            ->whereDate('appointmentDateTime',\Carbon\Carbon::now()->addWeeks($vaccineInterval))
                            ->first();

                    if (!$appointment) {
                        $appointment = new \App\Models\Appointment\Appointments();
                        $appointment->hn = $hn;
                        $appointment->clinicCode = 'BKK001';
                        $appointment->appointmentDateTime = \Carbon\Carbon::now()->addWeeks($vaccineInterval)->startOfHour();
                        $appointment->save();
                    }

                    $passportData["appointmentDate"] = \Carbon\Carbon::now()->addWeeks($vaccineInterval)->format('Y-m-d');
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
        $from = \Carbon\Carbon::parse($beginDate)->startOfDay()->toDateTimeString();
        $to = ($endDate) ? \Carbon\Carbon::parse($endDate)->endOfDay()->toDateTimeString() : $from;

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
}
