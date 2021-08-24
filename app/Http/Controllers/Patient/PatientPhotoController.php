<?php

namespace App\Http\Controllers\Patient;

use Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;
use TaNteE\LaravelModelApi\LaravelModelApi;
use TaNteE\LaravelModelApi\Http\Controllers\Asset\AssetController;

class PatientPhotoController extends Controller
{
    public static function addPhoto($data) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      $createDataValidator = Validator::make($data,[
        'hn' => 'required',
        'photoType' => 'required',
        'base64data' => 'required',
      ]);

      if ($createDataValidator->fails()) {
        foreach($createDataValidator->errors()->all() as $value) array_push($errorTexts,["errorText"=>$value]);
        $success = false;
      }

      if ($success) {
        $assetResult = AssetController::addAssetBase64($data['hn'],$data['base64data'],$data['photoType']);
        if (!$assetResult['success']) {
          $success = false;
          $errorTexts = array_merge($errorTexts,$assetResult['errorTexts']);
        } else {
          $returnModels = $assetResult['returnModels'];
        }
      }

      return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function getPhotos(Request $request,$hn) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];


      if (PatientController::isExistPatient($hn)) {
        $patient = \App\Models\Patient\Patients::find($hn);
        $returnModels = $patient->Photos();
        if (isset($request->perPage) && is_numeric($request->perPage)) {
          $returnModels = $returnModels->paginate($request->perPage)->appends(['perPage'=>$request->perPage]);
          $temp = $returnModels->makeHidden(['storage','storagePath']);
          $returnModels->data = $temp;
        } else {
          $returnModels = $returnModels->get();
          $returnModels = $returnModels->makeHidden(['storage','storagePath']);
        }
      } else {
        $success = false;
        array_push($errorTexts,["errorText" => 'No patient for HN '.$hn]);
      }

      return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function getPhoto($hn) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      if (PatientController::isExistPatient($hn)) {
        $patient = \App\Models\Patient\Patients::find($hn);
        $photoAsset = $patient->Photos()->first();
        if ($photoAsset != null) {
          $photoAsset->with(['base64data']);
          $returnModels = $photoAsset->only(['id','md5hash','base64data']);
        } else {
          $success = false;
          array_push($errorTexts,["errorText" => 'No photo for patient '.$hn]);
        }
      } else {
        $success = false;
        array_push($errorTexts,["errorText" => 'No patient for HN '.$hn]);
      }

      return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function deletePhoto($hn,$id,$md5hash) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      if (PatientController::isExistPatient($hn)) {
        return AssetController::deleteAsset($id,$md5hash);
      } else {
        $success = false;
        array_push($errorTexts,["errorText" => 'No patient for HN '.$hn]);
      }
      
      return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }
}
