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
      $documentData = [
        'hn' => $hn,
        'templateCode' => $templateCode,
        'data' => $data,
      ];
      if ($category!==null) $documentData['category'] = $category;
      if ($referenceId!==null) $documentData['referenceId'] = $referenceId;
      if ($encounterId!==null) $documentData['encounterId'] = $encounterId;
      if ($folder!==null) $documentData['folder'] = $folder;
      return self::addDocuments($documentData);
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

    public static function addScannedDocuments($documentData,$hn=null,$category=null,$encounterId=null,$referenceId=null,$folder=null,$isAppend=false) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      if (!empty($documentData) && (array_keys($documentData) !== range(0, count($documentData) - 1))) $documentData = array($documentData);

      DB::beginTransaction();

      foreach($documentData as $data) {
        if (\is_array($data) && isset($data['base64string'])) {
          $tmpData = explode(',',$data['base64string']);
          $tmpData = (count($tmpData)==1) ? $tmpData[0] : $tmpData[1];

          $tmpData = base64_decode($tmpData);
          try {
            $tmpData = \App\Utilities\Image::scaleBlobImage($tmpData,1000,1000);
            $QRCodeReader = new \Zxing\QrReader($tmpData,\Zxing\QrReader::SOURCE_TYPE_BLOB);
            $QRCodeData = $QRCodeReader->text();
            $QRCodeData = \json_decode($QRCodeData,true);
          } catch(\Exception $e) {
            $QRCodeData = [];
          }

          if (isset($QRCodeData['DocId'])) {
            $document = \App\Models\Document\Documents::withTrashed()->find($QRCodeData['DocId']);
            if ($document && $document->deleted_at) {
              $document->restore();
              $document->status = "draft";
              $document->save();

              $document = \App\Models\Document\Documents::find($QRCodeData['DocId']);
            }
          } else if ($hn!=null) {
            $document = self::addDocument($hn,'default_scan',null,$category,$encounterId,$referenceId,$folder);
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

          if ($success && $document) {
            if (isset($data['category']) && $data['category']!=null && $data['category']!='') $document->category = $data['category'];
            if (isset($data['referenceId']) && $data['referenceId']!=null && $data['referenceId']!='') $document->referenceId = $data['referenceId'];
            if (isset($data['folder']) && $data['folder']!=null && $data['folder']!='') $document->folder = $data['folder'];

            $data = array_only($data,['base64string']);
            
            if ($document->isScanned && ($isAppend || Carbon::now()->diffInMinutes($document->updated_at)<=5)) {
              $oldData = array_wrap($document->data);
              array_push($oldData,$data);
              $document->data = $oldData;
            } else {
              $document->data = [$data];
            }
            $document->isScanned = true;
            $document->status = 'approved';
            try {
              $document->save();
              $document->load(['template','encounter']);
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
