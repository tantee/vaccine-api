<?php

namespace App\Http\Controllers\Document;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Document\clsTbsCleaner;
use App\Document\clsMasterItem;
use App\Document\clsPlugin;
use App\Http\Controllers\Patient\PatientController;
use App\Http\Controllers\Document\DocumentController;
use App\Utilities\ArrayType;

use TheCodingMachine\Gotenberg\Client;
use TheCodingMachine\Gotenberg\DocumentFactory;
use TheCodingMachine\Gotenberg\MergeRequest;
use TheCodingMachine\Gotenberg\OfficeRequest;

use Uvinum\PDFWatermark\Pdf;
use Uvinum\PDFWatermark\Watermark;
use Uvinum\PDFWatermark\FpdiPdfWatermarker as PDFWatermarker;
use Uvinum\PDFWatermark\Position;

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

    public static function printDocumentRaw($documentId,$templateCode=null,$toPdf=true) {
      $document = \App\Models\Document\Documents::find($documentId);
      if ($document == null) return null;

      if ($templateCode == null)  $templateCode = $document->templateCode;
      if ($document->isScanned) $templateCode = "default_scan";
      if ($document->is_pdf) {
        $asset = \App\Models\Asset\Assets::find($document->data[0]['id']);
        if ($asset !== null) {
          $content = Storage::disk($asset->storage)->get($asset->storagePath);
          return $content;
        }
      }

      if (ArrayType::isAssociative($document->data)) $data = $document->data;
      else $data = ["data" => $document->data];

      $data['qrCodeData'] = \json_encode(['DocId' => $document->id]);

      $data['documentId'] = $document->id;
      $data['referenceId'] = $document->referenceId;
      $data['templateCode'] = $document->templateCode;
      $data['documentCategory'] = $document->category;
      $data['documentFolder'] = $document->folder;
      $data['documentNote'] = $document->note;
      $data['documentStatus'] = $document->status;
      $data['documentPatientAge'] = $document->patient_age;
      $data['documentPatientAgeEN'] = $document->patient_age_en;

      $data['created_at'] = $document->created_at;
      $data['created_by'] = $document->created_by;
      $data['updated_at'] = $document->updated_at;
      $data['updated_by'] = $document->updated_by;
      
      return self::genericPrintDocumentRaw($document->hn,$document->encounterId,$templateCode,$data,$document->id,$toPdf);
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

      $toPdf = (boolean)\App\Models\Document\Documents::whereIn('id',$documentIds)->where('data->isVoid',true)->count();
      $toPdf = $toPdf || (boolean)\App\Models\Document\Documents::whereIn('id',$documentIds)->get()->where('is_pdf',true)->count();

      $filenames = [];
      foreach($documentIds as $key => $documentId) {
        $rawData = self::printDocumentRaw($documentId,null,$toPdf);
        if ($toPdf) $filename = $tmpDirectory.'/'.$key.'.pdf';
        else $filename = $tmpDirectory.'/'.$key.'.docx';
        if ($rawData != null) {
          Storage::put($filename,$rawData);
          $filenames[] = $filename;
        }
      }

      $pdfFunction = ($toPdf) ? "mergePDF" : "convertToPDF";

      if (self::$pdfFunction($filenames,$tmpFilenamePDF)) {
        $returnData = Storage::get($tmpFilenamePDF);
      }

      Storage::deleteDirectory($tmpDirectory);

      return $returnData;
    }

    public static function genericPrintDocumentRaw($hn,$encounterId,$templateCode,$data,$documentId=null,$toPdf=true) {
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
        $patientData['age'] = $patient->age;
        $patientData['nationality'] = $patient->nationality;
        $patientData['race'] = $patient->race;
        $patientData['religion'] = $patient->religion;
        $patientData['primaryMobileNo'] = $patient->primaryMobileNo;
        $patientData['primaryTelephoneNo'] = $patient->primaryTelephoneNo;
        $patientData['primaryEmail'] = $patient->primaryEmail;
        $patientData['insurances'] = $patient->insurances->toArray();
        $patientData['allergies'] = null;

        if ($patient->Photos->count()>0) $patientData['photo'] = storage_path('app/'.$patient->Photos->first()->storagePath);

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
        $encounterData['doctorLicenseNo'] = $encounter->Doctor->licenseNo;

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

      if ($templatePath!=null) {
        $TBS = new \clsTinyButStrong();
        $TBS->Plugin(\TBS_INSTALL, 'clsOpenTBS');
        $TBS->Plugin(clsTbsCleaner::class);
        $TBS->Plugin(clsMasterItem::class);
        $TBS->Plugin(clsPlugin::class);
        $TBS->NoErr = true;
        
        $TBS->TplVars['tmpDirectory'] = $tmpDirectory;

        $TBS->LoadTemplate(storage_path('app/'.$templatePath),\OPENTBS_ALREADY_UTF8);
        self::merge($TBS,$data,$currPrm);

        $subfiles = $TBS->PlugIn(OPENTBS_GET_HEADERS_FOOTERS);

        foreach ($subfiles as $subfile) {
          $TBS->PlugIn(OPENTBS_SELECT_FILE, $subfile);
          self::merge($TBS,$data,$currPrm);
        }

        $TBS->Show(\OPENTBS_FILE,storage_path('app/'.$tmpFilename));

        if (isset($hn) && isset($templateCode) && isset($documentId)) Storage::copy($tmpFilename,'/documents/'.$hn.'/'.$templateCode.'/raw/'.$documentId.'_'.$tmpUniqId.'.docx');

        if ($toPdf && self::convertToPDF($tmpFilename,$tmpFilenamePDF)) {
          if (isset($data['isVoid']) && $data['isVoid']) {

            $tmpOriginal = dirname($tmpFilenamePDF).'/original.pdf';
            Storage::move($tmpFilenamePDF, $tmpOriginal);

            if (self::watermarkPDF($tmpOriginal,$tmpFilenamePDF,'void')) {
              $returnData = Storage::get($tmpFilenamePDF);
            } else {
              $returnData = Storage::get($tmpOriginal);
            }
            Storage::delete($tmpOriginal);
          } else {
            $returnData = Storage::get($tmpFilenamePDF);
          }
        } else {
          $returnData = Storage::get($tmpFilename);
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
            else if (!$TBS->GetBlockSource($key)) $TBS->MergeField($key,$value,false,$currPrm);
            else $TBS->MergeBlock($key,$value);
          } else {
            $TBS->MergeField($key,$value,false,$currPrm);
          }
        }
      }
    }

    private static function convertToPDF($filenames,$outputFilename) {
      $success = false;
      $UnoconvServ = env('UNOCONV_SERV',null);
      
      if (!is_array($filenames)) $filenames = [$filenames];

      if (($UnoconvServ != null) && ($UnoconvServ != "")) {
        try {
          $client = new Client($UnoconvServ, new \Http\Adapter\Guzzle6\Client());
          $files = [];
          foreach($filenames as $filename) {
            $files[] = DocumentFactory::makeFromPath(basename($filename), storage_path('app/'.$filename));
          }
          $request = new OfficeRequest($files);
          $client->store($request, storage_path('app/'.$outputFilename));
          
          $success = true;
          
          if (!App::environment('PROD')) {
            $tmpOriginal = dirname($outputFilename).'/original.pdf';
            Storage::move($outputFilename, $tmpOriginal);
            $success = self::watermarkPDF($tmpOriginal,$outputFilename,'test');
            Storage::delete($tmpOriginal);
          }
        } catch (\Exception $e) {
          $success = false;
        }
      }
      return $success;
    }

    private static function mergePDF($filenames,$outputFilename) {
      $success = false;
      $UnoconvServ = env('UNOCONV_SERV',null);

      if (!is_array($filenames)) $filenames = [$filenames];

      if (($UnoconvServ != null) && ($UnoconvServ != "")) {
        try {
          $client = new Client($UnoconvServ, new \Http\Adapter\Guzzle6\Client());
          $files = [];
          foreach($filenames as $filename) {
            $files[] = DocumentFactory::makeFromPath(basename($filename), storage_path('app/'.$filename));
          }
          $request = new MergeRequest($files);
          $client->store($request, storage_path('app/'.$outputFilename));
          $success = true;
        } catch (\Exception $e) {
          $success = false;
        }
      }
      return $success;
    }

    private static function watermarkPDF($filename,$outputFilename,$watermarkName) {
      $success = false;
      $watermarkFile = null;

      if (Storage::exists('/default/watermarks/'.$watermarkName.'.png')) $watermarkFile = storage_path('app/default/watermarks/'.$watermarkName.'.png');
      if ($watermarkFile==null && Storage::exists('/default/watermarks/'.$watermarkName.'.jpg')) $watermarkFile = storage_path('app/default/watermarks/'.$watermarkName.'.jpg');

      if ($watermarkFile!=null) {
        try {
          $pdf = new Pdf(storage_path('app/'.$filename));
          $watermark = new Watermark($watermarkFile); 
          $watermarker = new PDFWatermarker($pdf, $watermark);
          $watermarker->setPosition(new Position('MiddleCenter'));
          $watermarker->setAsBackground();
          $watermarker->savePdf(storage_path('app/'.$outputFilename));
          $success = true;
        } catch (\Exception $e) {
          throw $e;
          $success = false;
        }
      }
      
      return $success;
    }
}
