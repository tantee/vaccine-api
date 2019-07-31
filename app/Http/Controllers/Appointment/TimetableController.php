<?php

namespace App\Http\Controllers\Appointment;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Master\Clinics;
use App\Models\Appointment\DoctorsTimetablesClips;


class TimetableController extends Controller
{
    //
    private $dayOfWeeks = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
    public static function getTimetable($clinicCode,$doctorCode=null,$periodDate=null) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      if ($periodDate==null) $periodDate = Carbon::now();
      else $periodDate = Carbon::parse($periodDate);

      $clinics = Clinics::find($clinicCode);
      if ($clinics !== null) {
        $timetables = ($doctorCode==null) ? $clinics->Timetables : $clinics->Timetables()->where('doctorCode',$doctorCode)->get();
        $timetables = $timetables->only(['doctorCode','clinicCode','dayOfweek','beginTime','endTime','limitTotalCase','limitNewCase','dfHospitalPercent','dfMinimum','dfMaximum']);

        $periodBegin = $periodDate->firstOfMonth()->startOfDay();
        if ($periodBegin<Carbon::now()) $periodBegin = Carbon::now()->startOfDay();
        $periodEnd = $periodDate->LastOfMonth()->endOfDay();

        $generalClips = DoctorsTimetablesClips::where([
            ['clipDate','>=',$periodBegin],
            ['clipDate','<=',$periodEnd],
            ['clinicCode',null],
            ['doctorCode',null]
          ])->get();
        $clips = DoctorsTimetablesClips::Where([
            ['clipDate','>=',$periodBegin],
            ['clipDate','<=',$periodEnd],
            ['clinicCode',$clinicCode],
          ]);
        $clips = $clips->orderBy('clinicCode','doctorCode')->get();

        while($periodBegin<$periodEnd) {
          foreach($timetables->where('dayOfWeek',$periodBegin->dayOfWeekIso)->all() as $timetable) {
            $tmpTimetable = $timetable->toArray();
            $tmpTimetable['timetableDate'] = $periodBegin;

            $isClinicClose = false;
            $isUnappointable = false;

            foreach($generalClips->where('clipDate',$periodBegin)->all() as $clip) {
              if ($clip->isClinicClose) $isClinicClose = true;
              if ($clip->isUnappointable) $isUnappointable = true;
              foreach($clip->overrrideParameters as $key=>$value) {
                $tmpTimetable[$key] = $value;
              }
            }
            foreach($clips->where('clipDate',$periodBegin)->where('doctorCode',null)->all() as $clip) {
              if ($clip->isClinicClose) $isClinicClose = true;
              if ($clip->isUnappointable) $isUnappointable = true;
              foreach($clip->overrrideParameters as $key=>$value) {
                $tmpTimetable[$key] = $value;
              }
            }
            foreach($clips->where('clipDate',$periodBegin)->where('doctorCode',$doctorCode)->all() as $clip) {
              if ($clip->isClinicClose) $isClinicClose = true;
              if ($clip->isUnappointable) $isUnappointable = true;
              foreach($clip->overrrideParameters as $key=>$value) {
                $tmpTimetable[$key] = $value;
              }
            }
            $tmpTimetable['isClinicClose'] = $isClinicClose;
            $tmpTimetable['isUnappointable'] = $isUnappointable;

            $returnModels[] = $tmpTimetable;
          }
          $periodBegin->addDay();
        }

      } else {
        $success = false;
        array_push($errorTexts,["errorText" => "No clinic founded"]);
      }

      return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }
}
