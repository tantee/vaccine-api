<?php

namespace App\Http\Controllers\HL7;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Master\MasterController;
use App\Http\Controllers\Patient\PatientController;
use Carbon\Carbon;

use Aranyasen\HL7\Message;
use Aranyasen\HL7\Connection;
use Aranyasen\HL7\Request as HL7Request;
use Aranyasen\HL7\Response as HL7Response;
use Aranyasen\HL7\Segment;
use Aranyasen\HL7\Segments\{AIG,AIL,AIP,DG1,EVN,IN1,IN3,MSA,MSH,NTE,OBR,OBX,ORC,PID,PV1,RGS,SCH,TQ1};

class LISController extends Controller
{
    public static function createOrder($hn=19000018,$requestNo='RQ01-1000101',$labCode='Lab00001') {
      $patient = \App\Models\Patient\Patients::find($hn);
      $msg = new Message();
      $msh = new MSH();
      $msh->setSendingApplication('HIS');
      $msh->setSendingFacility('CAH');
      $msh->setReceivingApplication('LIS');
      $msh->setReceivingFacility('LabHouse');
      $msh->setDateTimeOfMessage(Carbon::now()->format('YmdHis'));
      $msh->setMessageType('ORM');
      $msh->setTriggerEvent('R01');
      $msh->setMessageControlId($requestNo);
      $msg->addSegment($msh);

      $pid = new PID();
      $pid->setPatientID('19000018');
      $pid->setPatientName([$patient->Name_real_en[0]['lastName'],
        $patient->Name_real_en[0]['firstName'],
        $patient->Name_real_en[0]['middleName'],
        MasterController::translateMaster('$NameSuffix',$patient->Name_real_en[0]['nameSuffix'],true),
        MasterController::translateMaster('$NamePrefix',$patient->Name_real_en[0]['namePrefix'],true)]);
      $pid->setDateTimeOfBirth(Carbon::parse($patient->dateOfBirth)->format('Ymd'));
      $pid->setSex(($patient->sex == 1) ? 'M' : 'F');
      $msg->addSegment($pid);

      $pv1 = new PV1();
      $pv1->setAttendingDoctor();
      $pv1->setReferringDoctor();
      $pv1->setConsultingDoctor();
      $msg->addSegment($pv1);

      $obr = new OBR();

      return $msg->toString(true);
    }
}
