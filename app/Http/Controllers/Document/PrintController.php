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

    public static function printBlankDocumentByTemplate($hn,$documentTemplate,$encounterId=null,$referenceId=null) {
      $documentTemplate = \App\Models\Document\DocumentsTemplates::find($documentTemplate);
      $patient = \App\Models\Patient\Patients::find($hn);
      if ($documentTemplate != null && $patient!=null) {
        $data = [];

        $patientData['hn'] = $patient->hn;
        $patientData['name_th'] = $patient->Name_th[0]->toArray();
        $patientData['name_en'] = $patient->Name_en[0]->toArray();
        $patientData['name_real_th'] = $patient->Name_real_th[0]->toArray();
        $patientData['name_real_en'] = $patient->Name_real_en[0]->toArray();
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
          'cId' => uniqid(),
          'tmpl' => $documentTemplate->id,
        ];

        $data['qrCodeData'] = \json_encode($qrCodeData);

        // $documentData = [
        //   'document_createdate' => $document->created_at,
        //   'document_creator' => $document->created_by,
        //   'document_updatedate' => $document->updated_at,
        //   'document_updater' => $document->updated_by,
        // ];
        //
        // $data['documentData'] = $documentData;

        return self::genericPrintDocumentBase64($documentTemplate->id,$data);
        //return $data;
      } else {
        return ['success'=>false];
      }
    }

    public static function genericPrintDocument($documentId) {
      $tmpUniqId = uniqid();
      $tmpDirectory = 'tmp/'.$tmpUniqId;
      $tmpFilename = $tmpDirectory.'/output.docx';
      $tmpFilenameTmpl = $tmpDirectory.'/template.docx';
      $tmpFilenamePDF = $tmpDirectory.'/output.pdf';

      $document = \App\Models\Document\Documents::find($documentId);
      if ($document == null) return null;

      $data = $document->data;
      $document->Patient;
      $document->Encounter;

      if ($document->Patient != null) {

      }

      if ($document->Patient != null) {

      }

      setlocale(LC_TIME, 'th_TH.utf8');
      $data['print_date'] = Carbon::now()->formatLocalized('%A %d %B %Y');
      $data['print_user'] = (Auth::user()) ? Auth::user()->name : '';

      foreach($data as $key=>$value) {
        if (\strpos($key,'base64')) {}
        if (\strpos($key,'file')) {}
        if (is_array($value) && \array_key_exists('assetId',$value)) {}
      }

      // Wait for good merge library

      // $tmpDefaultTemplate = null;
      // if ($document->Template->isNoDefaultHeader || $document->Template->isNoDefaultFooter) {
      //   if (!$document->Template->isNoDefaultHeader && !$document->Template->isNoDefaultFooter) {
      //     if ($document->Template->isRequiredEncounter) $tmpDefaultTemplate = 'tmpl_header_footer_all';
      //     else $tmpDefaultTemplate = 'tmpl_header_footer';
      //   }
      //   if (!$document->Template->isNoDefaultHeader && $document->Template->isNoDefaultFooter) {
      //     if ($document->Template->isRequiredEncounter) $tmpDefaultTemplate = 'tmpl_header_all';
      //     else $tmpDefaultTemplate = 'tmpl_header';
      //   }
      //   if ($document->Template->isNoDefaultHeader && !$document->Template->isNoDefaultFooter) $tmpDefaultTemplate = 'tmpl_footer';
      // }

      // if ($tmpDefaultTemplate!=null) $tmpDefaultTemplate = $tmpDefaultTemplate.'.docx';

      Storage::makeDirectory($tmpDirectory);
      
      $currPrm = [
        'tmpDirectory' => $tmpDirectory,
      ];

      $TBS = new \clsTinyButStrong();
      $TBS->Plugin(\TBS_INSTALL, 'clsOpenTBS');
      $TBS->Plugin(clsMasterItem::class);
      //$TBS->NoErr = true;

      $TBS->LoadTemplate(storage_path('app/'.$document->Template->printTemplate),\OPENTBS_ALREADY_UTF8);
      self::merge($TBS,$data,$currPrm);

      $TBS->PlugIn(\OPENTBS_SELECT_HEADER, \OPENTBS_DEFAULT);
      self::merge($TBS,$data,$currPrm);

      $TBS->PlugIn(\OPENTBS_SELECT_FOOTER, \OPENTBS_DEFAULT);
      self::merge($TBS,$data,$currPrm);

      $TBS->Show(\OPENTBS_FILE,storage_path('app/'.$tmpFilename));

      Storage::copy($tmpFilename,'/documents/'.$document->hn.'/'.$document->Template->templateCode.'/docx/'.$documentId.'_'.$tmpUniqId.'.docx');

      if (self::convertToPDF($tmpFilename,$tmpFilenamePDF)) {
        $returnData = Storage::get($tmpFilenamePDF);
      } else {
        $returnData = null;
      }

      Storage::deleteDirectory($tmpDirectory);

      return $returnData;
    }


    public static function genericPrintDocumentBase64($documentTemplate,$documentData,$documentId=null) {
      return base64_encode(self::genericPrintDocument($documentTemplate,$documentData,$documentId));
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
