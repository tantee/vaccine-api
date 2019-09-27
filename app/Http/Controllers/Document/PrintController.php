<?php

namespace App\Http\Controllers\Document;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Document\clsMasterItem;
use App\Document\clsPlugin;
use App\Http\Controllers\Patient\PatientController;
use App\Http\Controllers\Document\DocumentController;
use App\Utilities\ArrayType;

use TheCodingMachine\Gotenberg\Client;
use TheCodingMachine\Gotenberg\DocumentFactory;
use TheCodingMachine\Gotenberg\MergeRequest;
use TheCodingMachine\Gotenberg\OfficeRequest;

class PrintController extends Controller
{
    public static function printBlankDocument($documentTemplate,$hn=null,$encounterId=null,$referenceId=null,$data=null,$folder=null) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      $documentTemplate = \App\Models\Document\DocumentsTemplates::find($documentTemplate);

      if ($documentTemplate != null) {
        if ($documentTemplate->isRequiredPatientInfo && (empty($hn) || !PatientController::isExistPatient($hn))) {
          $success = false;
          array_push($errorTexts,["errorText" => "Require HN"]);
        }
        if ($documentTemplate->isRequiredEncounter && empty($encounterId)) {
          $success = false;
          array_push($errorTexts,["errorText" => "Require encounter ID"]);
        }

        if ($success) {
          $document = DocumentController::addDocument($hn,$documentTemplate->templateCode,$data,$documentTemplate->defaultCategory,$encounterId,$referenceId,$folder);
          if ($document["success"]) {
            $document = $document["returnModels"][0];
          } else {
            $success = false;
            array_push($errorTexts,["errorText" => 'Error creating new document']);
            $errorTexts = array_merge($errorTexts,$document["errorTexts"]);
          }
        }

        if ($success) {
          return self::printDocument($document->id);
        }
      } else {
        $success = false;
        array_push($errorTexts,["errorText" => "Invalid template code"]);
      }

      return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function printDocument($documentId,$templateCode=null) {
      if (is_array($documentId)) return base64_encode(self::printDocumentMultipleRaw($documentId));
      else return base64_encode(self::printDocumentRaw($documentId,$templateCode));
    }

    public static function genericPrintDocument($hn,$encounterId,$templateCode,$data,$documentId=null) {
      return base64_encode(self::genericPrintDocumentRaw($hn,$encounterId,$templateCode,$data,$documentId));
    }

    public static function printDocumentRaw($documentId,$templateCode=null) {
      $document = \App\Models\Document\Documents::find($documentId);
      if ($document == null) return null;

      if ($templateCode == null)  $templateCode = $document->templateCode;

      $data = $document->data;

      $data['qrCodeData'] = \json_encode(['DocId' => $document->id]);

      $data['documentId'] = $document->id;
      $data['referenceId'] = $document->referenceId;
      $data['templateCode'] = $document->templateCode;
      $data['documentCategory'] = $document->category;
      $data['documentFolder'] = $document->folder;
      $data['documentNote'] = $document->note;
      $data['documentStatus'] = $document->status;

      $data['created_at'] = $document->created_at;
      $data['created_by'] = $document->created_by;
      $data['updated_at'] = $document->updated_at;
      $data['updated_by'] = $document->updated_by;
      
      return self::genericPrintDocumentRaw($document->hn,$document->encounterId,$templateCode,$data,$document->id);
    }

    public static function printDocumentMultipleRaw($documentIds) {
      $tmpUniqId = uniqid();
      $tmpDirectory = 'tmp/'.$tmpUniqId;
      $tmpFilenamePDF = $tmpDirectory.'/output.pdf';

      $returnData = null;
      
      Storage::makeDirectory($tmpDirectory);

      if (ArrayType::isMultiDimension($documentIds)) {
        if (ArrayType::keyExists('documentId',$documentIds)) $documentIds = array_pluck($documentIds,'documentId');
        else $documentIds = array_pluck($documentIds,'id');
      }

      $filenames = [];
      foreach($documentIds as $key => $documentId) {
        $rawData = self::printDocumentRaw($documentId);
        $filename = $tmpDirectory.'/'.$key.'.pdf';
        if ($rawData != null) {
          Storage::put($filename,$rawData);
          $filenames[] = $filename;
        }
      }
      
      if (self::mergePDF($filenames,$tmpFilenamePDF)) {
        $returnData = Storage::get($tmpFilenamePDF);
      }

      Storage::deleteDirectory($tmpDirectory);

      return $returnData;
    }

    public static function genericPrintDocumentRaw($hn,$encounterId,$templateCode,$data,$documentId=null) {
      $tmpUniqId = uniqid();
      $tmpDirectory = 'tmp/'.$tmpUniqId;
      $tmpFilename = $tmpDirectory.'/output.docx';
      $tmpFilenameTmpl = $tmpDirectory.'/template.docx';
      $tmpFilenamePDF = $tmpDirectory.'/output.pdf';

      $returnData = null;

      if (!isset($data['qrCodeData'])) $data['qrCodeData'] = \json_encode([]);

      $template = \App\Models\Document\DocumentsTemplates::find($templateCode);

      if ($template != null) {
        $documentData['templateCode'] = $template->templateCode;
        $documentData['templateName'] = $template->templateName;
        $documentData['revisionId'] = $template->revisionId;
        $documentData['revisionDate'] = $template->revisionDate;
        $documentData['description'] = $template->description;

        $data['documentData'] = $documentData;
      }

      $patient = \App\Models\Patient\Patients::find($hn);

      if ($patient != null) {
        $patientData['hn'] = $patient->hn;
        $patientData['name_th'] = $patient->Name_th->toArray();
        $patientData['name_en'] = $patient->Name_en->toArray();
        $patientData['name_real_th'] = $patient->Name_real_th->toArray();
        $patientData['name_real_en'] = $patient->Name_real_en->toArray();
        $patientData['personId'] = $patient->personId;
        $patientData['sex'] = $patient->sex;
        $patientData['maritalStatus'] = $patient->maritalStatus;
        $patientData['dateOfBirth'] = $patient->dateOfBirth;
        $patientData['nationality'] = $patient->nationality;
        $patientData['race'] = $patient->race;
        $patientData['religion'] = $patient->religion;
        $patientData['primaryMobileNo'] = $patient->primaryMobileNo;
        $patientData['primaryTelephoneNo'] = $patient->primaryTelephoneNo;
        $patientData['primaryEmail'] = $patient->primaryEmail;

        $patientData['photo'] = storage_path('app/'.$patient->Photos->first()->storagePath);

        $data['patientData'] = $patientData;
      }

      $encounter = \App\Models\Registration\Encounters::find($encounterId);

      if ($encounter != null) {
        $encounterData['encounterId'] = $encounter->encounterId;
        $encounterData['encounterType'] = $encounter->encounterType;
        $encounterData['clinicName'] = $encounter->Clinic->clinicName;
        $encounterData['clinicNameEN'] = $encounter->Clinic->clinicNameEN;
        $encounterData['locationName'] = $encounter->Location->locationName;
        $encounterData['doctorNameTH'] = $encounter->Doctor->nameTH;
        $encounterData['doctorNameEN'] = $encounter->Doctor->nameEN;

        $data['encounterData'] = $encounterData;
      }


      $data['print_date'] = Carbon::now();
      $data['print_user'] = (Auth::guard('api')->check()) ? Auth::guard('api')->user()->username : 'Unidentified';


      Storage::makeDirectory($tmpDirectory);

      $currPrm = [
        'tmpDirectory' => $tmpDirectory,
      ];

      $templatePath = null;
      if ($template != null && $template->printTemplate!=null && Storage::exists($template->printTemplate)) $templatePath = $template->printTemplate;
      else if (Storage::exists('/default/documents/'.$templateCode.'.docx')) $templatePath = '/default/documents/'.$templateCode.'.docx';
      else if (Storage::exists('/default/documents/'.$templateCode.'.xlsx')) $templatePath = '/default/documents/'.$templateCode.'.xlsx';

      if ($templatePath!=null) {
        $TBS = new \clsTinyButStrong();
        $TBS->Plugin(\TBS_INSTALL, 'clsOpenTBS');
        $TBS->Plugin(clsMasterItem::class);
        $TBS->Plugin(clsPlugin::class);
        $TBS->NoErr = true;

        $TBS->LoadTemplate(storage_path('app/'.$templatePath),\OPENTBS_ALREADY_UTF8);
        self::merge($TBS,$data,$currPrm);

        $subfiles = $TBS->PlugIn(OPENTBS_GET_HEADERS_FOOTERS);

        foreach ($subfiles as $subfile) {
          $TBS->PlugIn(OPENTBS_SELECT_FILE, $subfile);
          self::merge($TBS,$data,$currPrm);
        }

        $TBS->Show(\OPENTBS_FILE,storage_path('app/'.$tmpFilename));

        if (isset($hn) && isset($templateCode) && isset($documentId)) Storage::copy($tmpFilename,'/documents/'.$hn.'/'.$templateCode.'/raw/'.$documentId.'_'.$tmpUniqId.'.docx');

        if (self::convertToPDF($tmpFilename,$tmpFilenamePDF)) {
          $returnData = Storage::get($tmpFilenamePDF);
        }
      }

      Storage::deleteDirectory($tmpDirectory);

      return $returnData;
    }

    private static function merge(&$TBS,$data,$currPrm) {
      if (is_array($data)) {
        foreach($data as $key=>$value) {
          if (\is_array($value)) {
            if ($key=="patientData") $TBS->MergeField($key,$value,false,$currPrm);
            else if ($key=="documentData") $TBS->MergeField($key,$value,false,$currPrm);
            else if ($key=="encounterData") $TBS->MergeField($key,$value,false,$currPrm);
            else if (!$TBS->MergeBlock($key,$value)) $TBS->MergeField($key,$value,false,$currPrm);
          } else {
            $TBS->MergeField($key,$value,false,$currPrm);
          }
        }
      }
    }

    private static function convertToPDF($filename,$outputFilename) {
      $success = false;
      $UnoconvServ = env('UNOCONV_SERV',null);

      if (($UnoconvServ != null) && ($UnoconvServ != "")) {
        try {
          $client = new Client($UnoconvServ, new \Http\Adapter\Guzzle6\Client());
          $files = [
              DocumentFactory::makeFromPath(basename($filename), storage_path('app/'.$filename))
          ];
          $request = new OfficeRequest($files);
          $client->store($request, $outputFilename);
        } catch (\Exception $e) {

        }
      }
      return $success;
    }

    private static function mergePDF($filenames,$outputFilename) {
      $success = false;
      $UnoconvServ = env('UNOCONV_SERV',null);

      if (($UnoconvServ != null) && ($UnoconvServ != "")) {
        try {
          $client = new Client($UnoconvServ, new \Http\Adapter\Guzzle6\Client());
          $files = [];
          foreach($filenames as $filename) {
            $files[] = DocumentFactory::makeFromPath(basename($filename), storage_path('app/'.$filename));
          }
          $request = new MergeRequest($files);
          $client->store($request, $outputFilename);
        } catch (\Exception $e) {

        }
      }
      return $success;
    }
}
