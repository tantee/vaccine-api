<?php

namespace App\Http\Controllers\Document;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DataController;
use App\Http\Controllers\Master\IdController;
use App\Http\Controllers\Patient\PatientController;
use Carbon\Carbon;

class DocumentController extends Controller
{
    //
    public static function addDocument($hn,$templateCode,$data,$category=null,$encounterId=null,$referenceId=null,$folder=null) {
      return self::addDocuments([
        'referenceId' => $referenceId,
        'hn' => $hn,
        'templateCode' => $templateCode,
        'data' => $data,
        'category' => $category,
        'encounterId' => $encounterId,
        'folder' => $folder,
      ]);
    }

    public static function addDocuments($documents) {
      return DataController::createModel($documents,\App\Models\Document\Documents::class);
    }

    public static function approveDocuments($documents) {
      if (is_array($documents)) $documents = array_pluck($documents,'id');
      else $documents = [$documents];

      $documents = \App\Models\Document\Documents::whereIn('id',$documents)->update(["status" => "approved"]);

      return $documents;
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

      DB::beginTransaction();

      foreach($documentData as $data) {
        if (\is_array($data) && isset($data['base64string'])) {
          $tmpData = explode(',',$data['base64string']);
          $tmpData = (count($tmpData)==1) ? $tmpData[0] : $tmpData[1];

          $tmpData = base64_decode($tmpData);

          try {
            $QRCodeReader = new \Zxing\QrReader($tmpData,\Zxing\QrReader::SOURCE_TYPE_BLOB);
            $QRCodeData = $QRCodeReader->text();
            $QRCodeData = \json_decode($qrCodeData,true);
          } catch(\Exception $e) {
            $QRCodeData = [];
          }

          if (isset($QRCodeData['DocId'])) {
            Log::info('DocID '.$QRCodeData['DocId']);
            $document = \App\Models\Document\Documents::find($QRCodeData['DocId']);
          } else if ($hn!=null) {
            $document = self::addDocument($hn,'default_scan',null,$category,$encounterId,$referenceId);
            if ($document["success"]) {
              $document = $document["returnModels"][0];
            } else {
              $success = false;
              array_push($errorTexts,["errorText" => 'Error creating new document']);
              $errorTexts = array_merge($errorTexts,$document["errorTexts"]);
            }
          } else {
            $success = false;
            array_push($errorTexts,["errorText" => 'No identification provided']);
          }

          if ($success) {
            if (isset($data['category']) && $data['category']!=null && $data['category']!='') $document->category = $data['category'];
            if (isset($data['referenceId']) && $data['referenceId']!=null && $data['referenceId']!='') $document->referenceId = $data['referenceId'];

            $data = array_only($data,['base64string']);
            
            if ($document->isScanned && ($isAppend || Carbon::now()->diffInMinute($document->updated_at)<=5)) {
              $oldData = $document->data;
              array_push($oldData,$data);
              $document->data = $oldData;
              Log::info('Document Append');
            } else {
              if (!empty($oldData)) {
                $oldRevision = $document->revision;
                array_push($oldRevision,[
                  "data" => $document->data,
                  "updated_by" => $document->updated_by,
                  "updated_at" => $document->updated_at,
                ]);
                $document->revision = $oldRevision;
                Log::info('Save revision');
              }
              $document->data = [$data];
            }
            $document->isScanned = true;
            $document->status = 'approved';
            try {
              $document->save();
              array_push($returnModels,$document);
            } catch (\Exception $e) {
              $returnModels = [];
              $success = false;
              array_push($errorTexts,["errorText" => $e->getMessage()]);
            }
          }
        } else {
          $success = false;
          array_push($errorTexts,["errorText" => 'Invalid document data']);
        }
      }

      if ($success) DB::commit();
      else DB::rollBack();

      return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function getDocuments(Request $request,$hn,$category=null,$encounterId=null,$referenceId=null) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];
      
      if (PatientController::isExistPatient($hn)) {
        return DataController::searchModelByRequest($request,\App\Models\Document\Documents::class);
      } else {
        $success = false;
        array_push($errorTexts,["errorText" => 'No patient for HN '.$hn]);
      }

      return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function deleteDocument($id) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      $document = \App\Models\Document\Documents::find($id);

      if ($document !== null) {
        $document->delete();
      } else {
        $success = false;
        array_push($errorTexts,["errorText" => 'No document matached']);
      }

      return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }
}
