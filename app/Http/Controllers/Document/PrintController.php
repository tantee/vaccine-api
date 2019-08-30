<?php

namespace App\Http\Controllers\Document;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Document\clsMasterItem;
use App\Http\Controllers\Patient\PatientController;
use App\Http\Controllers\Document\DocumentController;

class PrintController extends Controller
{
    public static function printDocument($documentId="abc",$data=[]) {
      $documentTemplate = "receipt";

      $document_qrcode_data = [
        'DocId' => $documentId,
      ];

      $document_qrcode_content = \json_encode($document_qrcode_data);

      setlocale(LC_TIME, 'th_TH.utf8');
      $data['document_header'] = '001';
      $data['document_qrcode'] = $document_qrcode_content;
      $data['document_barcode'] = '19000012';
      $data['print_date'] = Carbon::now()->formatLocalized('%A %d %B %Y');
      $data['print_user'] = (Auth::user()) ? Auth::user()->name : '';
      $data['document_date'] = '';
      $data['document_owner'] = '';

      $printedDocument = self::genericPrintDocument($documentTemplate,$data);

      return response($printedDocument)->header('Content-Type','application/pdf');
    }

    public static function printDocumentBase64($documentId="abc",$data=[]) {
      $documentTemplate = "receipt";

      $document_qrcode_data = [
        'hn' => '',
        'documentId' => $documentId,
      ];

      $document_qrcode_content = \json_encode($document_qrcode_data);

      setlocale(LC_TIME, 'th_TH.utf8');
      $data['document_header'] = '001';
      $data['document_qrcode'] = $document_qrcode_content;
      $data['document_barcode'] = '19000012';
      $data['print_date'] = Carbon::now()->formatLocalized('%A %d %B %Y');
      $data['print_user'] = (Auth::user()) ? Auth::user()->name : '';
      $data['document_date'] = '';
      $data['document_owner'] = '';

      $printedDocument = self::genericPrintDocumentBase64($documentTemplate,$data);

      return $printedDocument;
    }

    public static function printDocumentByTemplate($templateCode,$hn=null,$encounterId=null) {
      $success = true;
      $errorTexts = [];
      $returnModels = [];

      $template = \App\Models\Document\DocumentsTemplates::find($templateCode);

      if ($template!=null) {
        if ($template->isRequiredPatientInfo && !PatientController::isExistPatient($hn)) {
          $success = false;
          array_push($errorTexts,["errorText" => 'Require patient HN']);
        }

        if ($template->isRequiredEncounter && $encounterId == null) {
          $success = false;
          array_push($errorTexts,["errorText" => 'Require encounter ID']);
        }

        DocumentController::addDocument($hn,$templateCode,null,$template->defaultCategory);
      } else {
        $success = false;
        array_push($errorTexts,["errorText" => 'Document template not found']);
      }

      $document = \App\Models\Document\Documents::where('hn',$hn)->where('templateCode',$documentTemplate)->orderBy('id','desc')->first();
      if ($document != null) {
        $data = $document->data;

        $patient = $document->patient;
        $patientData['hn'] = $patient->hn;
        $patientData['name_th'] = $patient->Name_th;
        $patientData['name_en'] = $patient->Name_en;
        $patientData['name_real_th'] = $patient->Name_real_th;
        $patientData['name_real_en'] = $patient->Name_real_en;
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

        $data['patientData'] = $patientData;

        $qrCodeData = [
          'hn' => $hn,
          'pId' => $document->id,
          'cId' => uniqid(),
          'tmpl' => $documentTemplate,
        ];

        $data['qrCodeData'] = \json_encode($qrCodeData);

        $documentData = [
          'document_createdate' => $document->created_at,
          'document_creator' => $document->created_by,
          'document_updatedate' => $document->updated_at,
          'document_updater' => $document->updated_by,
        ];

        $data['documentData'] = $documentData;

        return self::genericPrintDocumentBase64($documentTemplate,$data);
      }
    }

    public static function printBlankDocumentByTemplate($documentTemplate,$hn=null,$encounterId=null,$referenceId=null) {
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
          array_push($errorTexts,["errorText" => "Require HN"]);
        }

        if ($success) {
          $document = DocumentController::addDocument($hn,$documentTemplate->templateCode,null,$documentTemplate->defaultCategory,$encounterId,$referenceId);
          if ($document["success"]) {
            $document = $document["returnModels"][0];
          } else {
            $success = false;
            array_push($errorTexts,["errorText" => 'Error creating new document']);
            $errorTexts = array_merge($errorTexts,$document["errorTexts"]);
          }
        }

        if ($success) {
          return self::genericPrintDocumentBase64($document->id);
        }
      } else {
        $success = false;
        array_push($errorTexts,["errorText" => "Invalid template code"]);
      }

      return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function genericPrintDocument($documentId) {
      $tmpUniqId = uniqid();
      $tmpDirectory = 'tmp/'.$tmpUniqId;
      $tmpFilename = $tmpDirectory.'/output.docx';
      $tmpFilenameTmpl = $tmpDirectory.'/template.docx';
      $tmpFilenamePDF = $tmpDirectory.'/output.pdf';

      $returnData = null;

      $document = \App\Models\Document\Documents::find($documentId);
      if ($document == null) return null;

      $data = $document->data;

      $data['qrCodeData'] = \json_encode(['DocId' => $document->id]);

      if ($document->Patient != null) {
        $patientData['hn'] = $document->Patient->hn;
        $patientData['name_th'] = $document->Patient->Name_th->toArray();
        $patientData['name_en'] = $document->Patient->Name_en->toArray();
        $patientData['name_real_th'] = $document->Patient->Name_real_th->toArray();
        $patientData['name_real_en'] = $document->Patient->Name_real_en->toArray();
        $patientData['personId'] = $document->Patient->personId;
        $patientData['sex'] = $document->Patient->sex;
        $patientData['maritalStatus'] = $document->Patient->maritalStatus;
        $patientData['dateOfBirth'] = $document->Patient->dateOfBirth;
        $patientData['nationality'] = $document->Patient->nationality;
        $patientData['race'] = $document->Patient->race;
        $patientData['religion'] = $document->Patient->religion;
        $patientData['primaryMobileNo'] = $document->Patient->primaryMobileNo;
        $patientData['primaryTelephoneNo'] = $document->Patient->primaryTelephoneNo;
        $patientData['primaryEmail'] = $document->Patient->primaryEmail;

        $data['patientData'] = $patientData;
      }

      if ($document->Encounter != null) {
        $encounterData['encounterId'] = '';
        
        $data['encounterData'] = $encounterData;
      }

      setlocale(LC_TIME, 'th_TH.utf8');
      $data['print_date'] = Carbon::now()->formatLocalized('%A %d %B %Y');
      $data['print_user'] = (Auth::guard('api')->check()) ? Auth::guard('api')->user()->username : 'Unidentified';

      foreach($data as $key=>$value) {
        if (\strpos($key,'base64')) {}
        if (\strpos($key,'file')) {}
        if (is_array($value) && \array_key_exists('assetId',$value)) {}
      }

      Storage::makeDirectory($tmpDirectory);
      
      $currPrm = [
        'tmpDirectory' => $tmpDirectory,
      ];

      $templatePath = null;
      if ($document->Template->printTemplate!=null && Storage::exists($document->Template->printTemplate)) $templatePath = $document->Template->printTemplate;
      else if (Storage::exists('/default/documents/'.$document->templateCode.'.docx')) $templatePath = '/default/documents/'.$document->templateCode.'.docx';
      else if (Storage::exists('/default/documents/'.$document->templateCode.'.xlsx')) $templatePath = '/default/documents/'.$document->templateCode.'.xlsx';

      if ($templatePath!=null) {
        $TBS = new \clsTinyButStrong();
        $TBS->Plugin(\TBS_INSTALL, 'clsOpenTBS');
        $TBS->Plugin(clsMasterItem::class);
        $TBS->NoErr = true;

        $TBS->LoadTemplate(storage_path('app/'.$document->Template->printTemplate),\OPENTBS_ALREADY_UTF8);
        self::merge($TBS,$data,$currPrm);

        $TBS->PlugIn(\OPENTBS_SELECT_HEADER, \OPENTBS_DEFAULT);
        self::merge($TBS,$data,$currPrm);

        $TBS->PlugIn(\OPENTBS_SELECT_FOOTER, \OPENTBS_DEFAULT);
        self::merge($TBS,$data,$currPrm);

        $TBS->Show(\OPENTBS_FILE,storage_path('app/'.$tmpFilename));

        Storage::copy($tmpFilename,'/documents/'.$document->hn.'/'.$document->Template->templateCode.'/raw/'.$documentId.'_'.$tmpUniqId.'.docx');

        if (self::convertToPDF($tmpFilename,$tmpFilenamePDF)) {
          $returnData = Storage::get($tmpFilenamePDF);
        }
      }

      Storage::deleteDirectory($tmpDirectory);

      return $returnData;
    }


    public static function genericPrintDocumentBase64($documentId) {
      return base64_encode(self::genericPrintDocument($documentId));
    }

    private static function merge(&$TBS,$data,$currPrm) {
      if (is_array($data)) {
        foreach($data as $key=>$value) {
          if (\is_array($value)) {
            if ($key=="patientData") $TBS->MergeField($key,$value,false,$currPrm);
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
        $client = new Client(['base_uri' =>  $UnoconvServ]);

        try {
          $response = $client->request('POST', 'convert/office', [
            'multipart' => [
                [
                    'name'     => 'files',
                    'contents' => Storage::get($filename),
                    'filename' => basename($filename)
                ],
              ]
          ]);
          if ($response->getStatusCode() == 200) {
            Storage::put($outputFilename,$response->getBody());
            $success = true;
          }
        } catch (\Exception $e) {

        }
      }
      return $success;
    }
}
