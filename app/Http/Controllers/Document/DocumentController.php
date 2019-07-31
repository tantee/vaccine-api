<?php

namespace App\Http\Controllers\Document;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DataController;
use App\Http\Controllers\Master\IdController;

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

    public static function addScannedDocument($documentData,$hn=null,$category=null,$encounterId=null,$referenceId=null) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      $tmpData = explode(',',$documentData);
      $tmpData = (count($tmpData)==1) ? tmpData[0] : tmpData[1];

      $tmpData = base64_decode($tmpData);

      try {
        $QRCodeReader = new \Zxing\QrReader($tmpData,\Zxing\QrReader::SOURCE_TYPE_BLOB);
        $QRCodeData = $QRCodeReader->text();
        $QRCodeData = \json_decode($qrCodeData,true);
      } catch(\Exception $e) {
        $QRCodeData = [];
      }

      if (isset($QRCodeData['tmpl'])) {
        $documentTemplate = \App\Models\Document\DocumentsTemplates::find($QRCodeData['tmpl']);
      }

      if ($hn==null && isset($QRCodeData['hn'])) $hn = $QRCodeData['hn'];
      if ($category==null) {
        if (isset($QRCodeData['cat'])) $category = $QRCodeData['cat'];
        else if ($documentTemplate != null && $documentTemplate->defaultCategory != null) $category = $documentTemplate->defaultCategory;
      }
      if ($referenceId==null && isset($QRCodeData['ref'])) $referenceId = $QRCodeData['ref'];
      if ($encounterId==null && isset($QRCodeData['enc'])) $encounterId = $QRCodeData['enc'];
      if (isset($QRCodeData['pId'])) {
        $parentDocument = \App\Models\Document\Documents::find($QRCodeData['pId']);
      }
      if (isset($QRCodeData['cId'])) $copyId = $QRCodeData['cId'];
      else $copyId = uniqid();

      if ($hn!=null || $parentDocument!=null) {
        $documentAsset = \App\Http\Controllers\Asset\AssetController::addAssetBase64((($parentDocument) ? $parentDocument->hn : $hn),$documentData);

        $newDocument = \App\Models\Document\Documents::firstOrNew(['copyId'=>$copyId]);
        $newDocument->hn = ($parentDocument) ? $parentDocument->hn : $hn;
        $newDocument->referenceId = ($parentDocument) ? $parentDocument->referenceId : $referenceId;
        $newDocument->encounterId = ($parentDocument) ? $parentDocument->encounterId : $encounterId;;
        $newDocument->category = ($parentDocument) ? $parentDocument->category : $category;;
        $newDocument->templateCode = 'document_scanned';
        $newDocument->data[] = $documentAsset;
        $newDocument->isScanned = true;
        $newDocument->parentId = ($parentDocument) ? $parentDocument->id : null;
        $newDocument->save();

        $returnModels = $newDocument;
      } else {
        $success = false;
        array_push($errorTexts,["errorText" => 'Missing patient\'s HN']);
      }

      return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }
}
