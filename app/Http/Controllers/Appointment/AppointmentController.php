<?php

namespace App\Http\Controllers\Appointment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Export\MOPHExportController;

class AppointmentController extends Controller
{
    public static function changeAppointmentDate($appointments,$newDate,$beginTime=9,$numPerHour=50,$dryrun=false) {
        $newDate = \Carbon\Carbon::parse($newDate);

        if (!($appointments instanceof \Illuminate\Database\Eloquent\Collection)) {
            $fromDate = \Carbon\Carbon::parse($appointments);
            $appointments = \App\Models\Appointment\Appointments::whereDate('appointmentDateTime',$fromDate)->get();
        }
        
        $count = 0;

        foreach($appointments as $appointment) {
            $appointment->appointmentDateTime = $appointment->appointmentDateTime->setDateFrom($newDate)->hour($beginTime);

            $smsMessage = \App\Models\Master\MasterItems::where('groupKey','smsMessages')->where('itemCode','changeAppointment')->first();
            if ($smsMessage) {
                $smsMessage = $smsMessage->itemValue;
                $smsMessage = str_replace('{newDate}',$newDate->locale('th_TH')->isoFormat('D MMM')." ".substr($newDate->year+543,2,2)." ".$beginTime.":00",$smsMessage);
            }
            
            if (!$dryrun) {
                if (empty($smsMessage) || self::sendSms($appointment->patient->primaryMobileNo,$smsMessage)) {
                    $appointment->save();
                    $count = $count+1;
                    if ($count >= $numPerHour) {
                        $beginTime = $beginTime + 1;
                        $count = 0;
                    }
                }
            } else {
                \Log::info($appointment->appointmentDateTime->toDateTimeString()." ".$appointment->patient->hn." ".$appointment->patient->primaryMobileNo." ".$smsMessage);
                $count = $count+1;
                if ($count >= $numPerHour) {
                    $beginTime = $beginTime + 1;
                    $count = 0;
                }
            }
        }
    }

    public static function adjustVaccineInterval($adminDate,$productCode) {

    }

    public static function adjustVaccineIntervalSingle($hn,$dryrun=false) {
        $vaccineHistory = \App\Models\Document\Documents::where('hn',$hn)->where('templateCode','cv19-vaccine-administration')->where('status','approved')->orderBy('id')->get();
        if ($vaccineHistory->count() > 0) {
            $vaccineCount = $vaccineHistory->count();
            $lastVaccineDate = $ramaVaccineHistory->max('created_at');
            $productCode = $vaccineHistory->first()->data["productCode"];

            $vaccine = \App\Models\Master\MasterItems::where('groupKey','covid19Vaccine')->where('itemCode',$productCode)->first();

            if ($vaccine) {
                $mophTarget = MOPHExportController::getTarget($hn);
                if (isset($mophTarget['vaccine_history_count']) && $mophTarget['vaccine_history_count']>$vaccineCount) {
                    $vaccineCount = $mophTarget['vaccine_history_count'];
                    if (isset($mophTarget['vaccine_history'])) {
                        $targetLastVaccineDate = collect($mophTarget['vaccine_history'])->map(function($item) { return \Carbon\Carbon::parse($item["immunization_datetime"])->timezone(config('app.timezone'));})->max();
                        if (empty($lastVaccineDate) || $lastVaccineDate->isBefore($targetLastVaccineDate)) $lastVaccineDate = $targetLastVaccineDate;
                    }
                }
                if ($vaccineCount < $vaccine->properties["vaccine_total_dose"]) {
                    $properAppointmentDateTime = $lastVaccineDate->addWeeks($vaccine->properties["vaccine_interval"]+1);
                    $appointment = \App\Models\Appointment\Appointments::where('hn',$hn)->orderBy('appointmentDateTime')->first();

                    if ($appointment) {
                        if ($appointment->appointmentDateTime->isAfter($properAppointmentDateTime)) {
                            $newDate = $lastVaccineDate->addWeeks($vaccine->properties["vaccine_interval"]);

                            $smsMessage = \App\Models\Master\MasterItems::where('groupKey','smsMessages')->where('itemCode','adjustAppointment')->first();
                            if ($smsMessage) {
                                $smsMessage = $smsMessage->itemValue;
                                $smsMessage = str_replace('{newDate}',$newDate->locale('th_TH')->isoFormat('D MMM')." ".substr($newDate->year+543,2,2),$smsMessage);
                            }

                            if (!$dryrun) {
                                if (empty($smsMessage) || self::sendSms($appointment->patient->primaryMobileNo,$smsMessage)) {
                                    $appointment->appointmentDateTime = $newDate;
                                    $appointment->save();
                                }
                            } else {
                                \Log::info($appointment->appointmentDateTime->toDateTimeString()." to ".$newDate->toDateTimeString()." ".$appointment->patient->hn." ".$appointment->patient->primaryMobileNo." ".$smsMessage);
                            }
                        }
                    }
                }
            }
        }
    }

    public static function moveSecondDose1($dryrun=true) {
        $beginTime = \Carbon\Carbon::parse('2021-08-30 08:00');
        $numPerPeriod = 250;

        $documents = \App\Models\Document\Documents::where('templateCode','cv19-vaccine-administration')
                        ->where('status','approved')
                        ->where('data->productCode','05000456068253')
                        ->whereDate('created_at','>=','2021-06-07')
                        ->whereDate('created_at','<=','2021-06-11')
                        ->get();
        \Log::info($documents->count());

        $count = 0;
        foreach($documents as $document) {
            $oldAppointments = \App\Models\Appointment\Appointments::where('hn',$document->hn)->whereDate('appointmentDateTime','>=',\Carbon\Carbon::now())->get();
            try {
                $previousVisitCount = 0;
                $mophTarget = MOPHExportController::getTarget($document->hn);
                if (isset($mophTarget['vaccine_history_count'])) $previousVisitCount = $mophTarget['vaccine_history_count'];
            } catch(\Exception $e) {
                \Log::error('MOPH Check error '.$document->hn,["Message"=>$e->getMessage()]);
            }

            if ($previousVisitCount < 2) {
                $appointment = new \App\Models\Appointment\Appointments();
                $appointment->hn = $document->hn;
                $appointment->clinicCode = 'VACCINE';
                $appointment->doctorCode = 'CN01';
                $appointment->appointmentType = 'VACCINE';
                $appointment->appointmentActivity = "202";
                $appointment->appointmentDateTime = $beginTime;
                
                $smsMessage = 'ขอเลื่อนให้ท่านมารับวัคซีนเข็ม 2 เร็วขึ้น เป็น {newDate}';
                $smsMessage = str_replace('{newDate}',$beginTime->locale('th_TH')->isoFormat('D MMM')." ".substr($beginTime->year+543,2,2)." ".$beginTime->format('H:i')." น.",$smsMessage);

                if (!$dryrun) {
                    if (empty($smsMessage) || self::sendSms($document->patient->primaryMobileNo,$smsMessage)) {
                        $appointment->save();
                        $count++;
                        foreach($oldAppointments as $oldAppointment) $oldAppointment->delete();
                    }
                } else {
                    \Log::info($appointment->appointmentDateTime->toDateTimeString()." ".$appointment->patient->hn." ".$appointment->patient->primaryMobileNo." ".$smsMessage);
                    $count++;
                }
            } else {
                if (!$dryrun) foreach($oldAppointments as $oldAppointment) $oldAppointment->delete();
                else \Log::info('remove old appointment '.$document->hn);
            }

            if ($count>=$numPerPeriod) {
                $count=0;
                $beginTime = $beginTime->addMinutes(30);
            }
        }
    }

    public static function moveSecondDose2($dryrun=true) {
        $beginTime = \Carbon\Carbon::parse('2021-08-31 08:00');
        $numPerPeriod = 210;

        $documents = \App\Models\Document\Documents::where('templateCode','cv19-vaccine-administration')
                        ->where('status','approved')
                        ->where('data->productCode','05000456068253')
                        ->whereDate('created_at','>=','2021-06-12')
                        ->whereDate('created_at','<=','2021-06-14')
                        ->get();
        \Log::info($documents->count());

        $count = 0;
        foreach($documents as $document) {
            $oldAppointments = \App\Models\Appointment\Appointments::where('hn',$document->hn)->whereDate('appointmentDateTime','>=',\Carbon\Carbon::now())->get();
            try {
                $previousVisitCount = 0;
                $mophTarget = MOPHExportController::getTarget($document->hn);
                if (isset($mophTarget['vaccine_history_count'])) $previousVisitCount = $mophTarget['vaccine_history_count'];
            } catch(\Exception $e) {
                \Log::error('MOPH Check error '.$document->hn,["Message"=>$e->getMessage()]);
            }

            if ($previousVisitCount < 2) {
                $appointment = new \App\Models\Appointment\Appointments();
                $appointment->hn = $document->hn;
                $appointment->clinicCode = 'VACCINE';
                $appointment->doctorCode = 'CN01';
                $appointment->appointmentType = 'VACCINE';
                $appointment->appointmentActivity = "202";
                $appointment->appointmentDateTime = $beginTime;

                $smsMessage = 'ขอเลื่อนให้ท่านมารับวัคซีนเข็ม 2 เร็วขึ้น เป็น {newDate}';
                $smsMessage = str_replace('{newDate}',$beginTime->locale('th_TH')->isoFormat('D MMM')." ".substr($beginTime->year+543,2,2)." ".$beginTime->format('H:i')." น.",$smsMessage);

                if (!$dryrun) {
                    if (empty($smsMessage) || self::sendSms($document->patient->primaryMobileNo,$smsMessage)) {
                        $appointment->save();
                        $count++;
                        foreach($oldAppointments as $oldAppointment) $oldAppointment->delete();
                    }
                } else {
                    \Log::info($appointment->appointmentDateTime->toDateTimeString()." ".$appointment->patient->hn." ".$appointment->patient->primaryMobileNo." ".$smsMessage);
                    $count++;
                }
            } else {
                if (!$dryrun) foreach($oldAppointments as $oldAppointment) $oldAppointment->delete();
                else \Log::info('remove old appointment '.$document->hn);
            }

            if ($count>=$numPerPeriod) {
                $count=0;
                $beginTime = $beginTime->addMinutes(30);
            }
        }
    }

    public static function sendAppointmentSMS($appointments,$smsMessagesCode,$dryrun=false) {
        if (!($appointments instanceof \Illuminate\Database\Eloquent\Collection)) {
            $fromDate = \Carbon\Carbon::parse($appointments);
            $appointments = \App\Models\Appointment\Appointments::whereDate('appointmentDateTime',$fromDate)->get();
        }

        $count = 0;

        foreach($appointments as $appointment) {
            $smsMessage = \App\Models\Master\MasterItems::where('groupKey','smsMessages')->where('itemCode',$smsMessagesCode)->first();
            if ($smsMessage) {
                $smsMessage = $smsMessage->itemValue;
                if (!$dryrun) {
                    self::sendSms($appointment->patient->primaryMobileNo,$smsMessage);
                } else {
                    \Log::info($appointment->appointmentDateTime->toDateTimeString()." ".$appointment->patient->hn." ".$appointment->patient->primaryMobileNo." ".$smsMessage);
                }
            }
        }
    }

    public static function sendSms($smsMobileNo,$message) {
        $ApiUrl = "http://wsback.rama.mahidol.ac.th/SMSapi/api/SendsmsNows/SendNow";
        $ApiMethod = "POST";

        $requestData = [
            'headers' => [
              'Accept' => 'application/json',
              'Content-Type' => 'application/json',
            ],
            'verify' => false
          ];

          $smsData = [
            "UserId"=> "appcov",
            "Systemname"=> "appcovid",
            "Systempass"=> "C0v@@p",
            "MobileNo"=> substr(str_replace(["-"," "],"",$smsMobileNo),0,10),
            "Message"=> $message,
            "SendToName"=> $smsMobileNo,
            "Schedule"=> \Carbon\Carbon::now()->toDateTimeString(), 
            "Clientname"=> "RamaCare",
            "ClientIP"=> "10.6.85.233"
          ];

        $requestData['json'] = $smsData;
        $requestData['timeout'] = 5;

        try {
            \Log::info("Sending SMS to ".$smsMobileNo.". Message : ".$message);
            $client = new \GuzzleHttp\Client();
            $res = $client->request($ApiMethod,$ApiUrl,$requestData);

            $httpResponseCode = $res->getStatusCode();
            $httpResponseReason = $res->getReasonPhrase();

            if ($httpResponseCode==200) {
                $smsResponse = json_decode((String)$res->getBody(),true);
                if (isset($smsResponse["status"]) && $smsResponse["status"]=="0") return true;
                else {
                    \Log::error((String)$res->getBody());
                    return false;
                }
            } else {
                return false;
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
          \Log::error("Error calling to $ApiMethod $ApiUrl.",["Message"=>$e->getMessage(),"RequestData"=>$requestData]);

          return false;
        }
    }
}
