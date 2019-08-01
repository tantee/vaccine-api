<?php

namespace App\Http\Controllers\Patient;

use Validator;
use Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DataController;
use App\Http\Controllers\Asset\AssetController;

class PatientController extends Controller
{
    public static function createPatient($data) {

      $success = true;
      $errorTexts = [];
      $returnModels = [];

      $createDataValidator = Validator::make($data,[
        'patient' => 'required|array',
        'patient.dateOfBirth' => 'required|date_format:Y-m-d',
        'patient.personIdType' => 'required',
        'patient.personId' => 'required',
        'patient.sex' => 'required|integer',
        'patient.primaryMobileNo' => 'required',
        'name' => 'required|array',
        'address' => 'sometimes|required|array',
        'relative' => 'sometimes|required|array'
      ]);

      if ($createDataValidator->fails()) {
        foreach($createDataValidator->errors()->all() as $value) array_push($errorTexts,["errorText"=>$value]);
        $success = false;
      }

      if ($success) {
        if ($data['patient']['personIdType']==1) {
          if (PatientController::isExistPersonId($data['patient']['personIdType'],$data['patient']['personId'])) {
            $success = false;
            array_push($errorTexts,["errorText" => 'Person ID is duplicated.']);
          }
        }
      }

      if ($success) {
        if (!isset($data['patient']['hn'])) $hn = \App\Http\Controllers\Master\IdController::issueId('hn',date('y'),5);
        else $hn = $data['patient']['hn'];

        $data['patient'] = array_merge(['hn'=>$hn],$data['patient']);
        data_fill($data['name'],'*.hn',$hn);
        data_fill($data['address'],'*.hn',$hn);
        data_fill($data['relative'],'*.hn',$hn);

        if ($success && isset($data['photoIDCard'])) {
          $assetResult = AssetController::addAssetBase64($hn,$data['photoIDCard'],'id_photo');
          if (!$assetResult['success']) {
            $success = false;
            $errorTexts = array_merge($errorTexts,$assetResult['errorTexts']);
          }
        }
        if ($success && isset($data['documentIDCard'])) {
          $assetResult = AssetController::addAssetBase64($hn,$data['documentIDCard'],'id_document');
          if ($assetResult['success']) {
            $data['patient']['personIdVerified'] = true;
            $data['patient']['personIdDetail'] = $assetResult['returnModels'];
          } else {
            $success = false;
            $errorTexts = array_merge($errorTexts,$assetResult['errorTexts']);
          }
        }

        if ($success) {
          DB::beginTransaction();
          try {
            $returnResults = [];
            if (isset($data['patient'])) {
              $validator = [];
              $returnResults['patient'] = DataController::createModel($data['patient'],\App\Models\Patient\Patients::class,$validator,[],true);
            }
            if (isset($data['name'])) {
              $validator = [];
              $returnResults['name'] = DataController::createModel($data['name'],\App\Models\Patient\PatientsNames::class,$validator,[],true);
            }
            if (isset($data['address'])) {
              $validator = [];
              $returnResults['address'] = DataController::createModel($data['address'],\App\Models\Patient\PatientsAddresses::class,$validator,[],true);
            }
            if (isset($data['relative'])) {
              $validator = [];
              $returnResults['relative'] = DataController::createModel($data['relative'],\App\Models\Patient\PatientsRelatives::class,$validator,[],true);
            }

            $docApplicationData = [
              'hn' => $hn,
              'templateCode' => 'application_form',
              'data' => $data
            ];
            $returnResults['document_application'] = DataController::createModel($docApplicationData,\App\Models\Document\Documents::class,[],[],true);

            foreach($returnResults as $key=>$returnResult) {
              $success = $success && $returnResult['success'];
              if ($returnResult['success']) $returnModels[$key] = $returnResult['returnModels'];
              else {
                data_fill($returnResult,'errorTexts.*.field',$key);
                $errorTexts = array_merge($errorTexts,$returnResult['errorTexts']);
              }
            }

            if (!$success) {
              DB::rollBack();
              $returnModels = [];
            }
          } catch (\Exception $e) {
            DB::rollBack();
            $returnModels = [];
            $success = false;
            array_push($errorTexts,["errorText" => $e->getMessage()]);
          }
          DB::commit();
        }
      }

      return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function verifyPatient($hn,$personIdDetail,$personIdDocument=null) {
      if (isset($personIdDocument)) {

      } else if (\App\Utilities\JSON::isJson($personIdDetail)) {

      }
    }

    public static function verifyPatientId($data) {

    }

    public static function isExistPatient($hn) {
      $patient = \App\Models\Patient\Patients::where('hn',$hn);

      if ($patient->count()) return true;
      else return false;
    }

    public static function isExistPersonId($personIdType,$personId) {
      $patient = \App\Models\Patient\Patients::where([['personIdType',$personIdType],['personId',$personId]]);

      if ($patient->count()) return true;
      else return false;
    }

    public static function getPatient($hn) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      $patient = \App\Models\Patient\Patients::find($hn);
      if ($patient != null) {
        $patient = $patient->with(['Name_th','Name_en']);
        $patient = $patient->first()->makeHidden(['personIdDetail']);
        $returnModels = $patient;
      } else {
        $success = false;
        array_push($errorTexts,["errorText" => 'No patient for HN '.$hn]);
      }

      return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }
}