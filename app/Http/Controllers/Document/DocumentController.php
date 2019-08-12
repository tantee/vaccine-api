<?php

namespace App\Http\Controllers\Document;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DataController;
use App\Http\Controllers\Master\IdController;
use App\Http\Controllers\Patient\PatientController;
use Carbon\Carbon;

class DocumentController extends Controller
{
    //
    public static function addDocument($hn,$templateCode,$data,$category=null,$encounterId=null,$referenceId=null) {
      return addDocuments([
        'referenceId' => $referenceId,
        'hn' => $hn,
        'templateCode' => $templateCode,
        'data' => $data,
        'category' => $category,
        'encounterId' => $encounterId,
      ]);
    }

    public static function addDocuments($documents) {
      return DataController::createModel($documents,\App\Models\Document\Documents::class);
    }

    public static function getDocumentByTemplate($hn,$templateCode) {
      $document = \App\Models\Document\Documents::where('templateCode',$templateCode)->orderBy('id','desc')->first();
      return $document;
    }

    public static function addScannedDocuments($documentData,$hn=null,$category=null,$encounterId=null,$referenceId=null,$isAppend=false) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      if (array_keys($documentData) !== range(0, count($documentData) - 1)) $documentData = array(documentData);

      foreach($documentData as $data) {
        if (\is_array($data) && isset($data['base64string'])) {
          $tmpData = explode(',',$data['base64string']);
          $tmpData = (count($tmpData)==1) ? tmpData[0] : tmpData[1];

          $tmpData = base64_decode($tmpData);

          try {
            $QRCodeReader = new \Zxing\QrReader($tmpData,\Zxing\QrReader::SOURCE_TYPE_BLOB);
            $QRCodeData = $QRCodeReader->text();
            $QRCodeData = \json_decode($qrCodeData,true);
          } catch(\Exception $e) {
            $QRCodeData = [];
          }

          if (isset($QRCodeData['DocId'])) {
            $document = \App\Models\Document\Documents::find($QRCodeData['DocId']);
          } else if ($hn!=null) {
            $document = self::addDocument($hn,'default_scan',null,$category,$encounterId,$referenceId);
            if ($document["success"]) {
              $document = $document["returnModels"][0];
            } else {
              $success = false;
              array_push($errorTexts,["errorText" => 'Error creating new document']);
            }
          } else {
            $success = false;
            array_push($errorTexts,["errorText" => 'No identification provided']);
          }

          if ($success) {
            if ($document->isScanned && ($isAppend || Carbon::now()->diffInMinute($document->updated_at)<=5)) array_push($document->data,$data);
            else {
              if (!empty($document->data)) {
                array_push($document->revision,[
                  "data" => $document->data,
                  "updated_by" => $document->updated_by,
                  "updated_at" => $document->updated_at,
                ]);
              }
              $document->data = [$data];
            }
            $document->isScanned = true;

            $document->save();
            array_push($returnModels,$document);
          }
        } else {
          $success = false;
          array_push($errorTexts,["errorText" => 'Invalid document data']);
        }
      }

      return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function getDocuments(Request $request,$hn,$category=null,$encounterId=null,$referenceId=null) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];


      if (PatientController::isExistPatient($hn)) {
        $patient = \App\Models\Patient\Patients::find($hn);
        $returnModels = $patient->Documents();
        if ($category!=null) $returnModels = $returnModels->where('category',$category);
        if ($encounterId!=null) $returnModels = $returnModels->where('encounterId',$encounterId);
        if ($referenceId!=null) $returnModels = $returnModels->where('referenceId',$referenceId);
        if (isset($request->perPage) && is_numeric($request->perPage)) {
          $returnModels = $returnModels->paginate($request->perPage)->appends(['perPage'=>$request->perPage]);
        } else {
          $returnModels = $returnModels->get();
        }
      } else {
        $success = false;
        array_push($errorTexts,["errorText" => 'No patient for HN '.$hn]);
      }

      return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }
}
