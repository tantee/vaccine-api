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
use App\Document\clsPlugin;
use App\Http\Controllers\Patient\PatientController;
use App\Http\Controllers\Document\DocumentController;

class PrintController extends Controller
{
    public static function printBlankDocumentByTemplate($documentTemplate,$hn=null,$encounterId=null,$referenceId=null,$data=null,$folder=null) {
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
          return self::genericPrintDocumentBase64($document->id);
        }
      } else {
        $success = false;
        array_push($errorTexts,["errorText" => "Invalid template code"]);
      }

      return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function genericPrintDocument($documentId,$templateCode=null) {
      $tmpUniqId = uniqid();
      $tmpDirectory = 'tmp/'.$tmpUniqId;
      $tmpFilename = $tmpDirectory.'/output.docx';
      $tmpFilenameTmpl = $tmpDirectory.'/template.docx';
      $tmpFilenamePDF = $tmpDirectory.'/output.pdf';

      $returnData = null;

      $document = \App\Models\Document\Documents::find($documentId);
      if ($document == null) return null;

      if ($templateCode != null) $document->templateCode = $templateCode;

      $data = $document->data;

      $data['qrCodeData'] = \json_encode(['DocId' => $document->id]);

      if ($document->Template != null) {
        $documentData['templateCode'] = $document->Template->templateCode;
        $documentData['templateName'] = $document->Template->templateName;
        $documentData['revisionId'] = $document->Template->revisionId;
        $documentData['revisionDate'] = $document->Template->revisionDate->locale('th_TH')->isoFormat('DD/MM/YYYY');
        $documentData['description'] = $document->Template->description;

        $data['documentData'] = $documentData;
      }

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

        $patientData['photo'] = storage_path('app/'.$document->Patient->Photos->first()->storagePath);

        $data['patientData'] = $patientData;
      }

      if ($document->Encounter != null) {
        $encounterData['encounterId'] = $document->Encounter->encounterId;
        $encounterData['encounterType'] = $document->Encounter->encounterType;
        $encounterData['clinicName'] = $document->Encounter->Clinic->clinicName;
        $encounterData['clinicNameEN'] = $document->Encounter->Clinic->clinicNameEN;
        $encounterData['locationName'] = $document->Encounter->Location->locationName;
        $encounterData['doctorNameTH'] = $document->Encounter->Doctor->nameTH;
        $encounterData['doctorNameEN'] = $document->Encounter->Doctor->nameEN;
        
        $data['encounterData'] = $encounterData;
      }

      $data['documentId'] = $document->id;
      $data['referenceId'] = $document->referenceId;
      $data['templateCode'] = $document->templateCode;
      $data['documentCategory'] = $document->category;
      $data['documentFolder'] = $document->folder;
      $data['documentNote'] = $document->note;
      $data['documentStatus'] = $document->status;


      $data['print_date'] = Carbon::now()->locale('th_TH')->isoFormat('dddd DD MMMM YYYY');
      $data['print_user'] = (Auth::guard('api')->check()) ? Auth::guard('api')->user()->username : 'Unidentified';

      $data['created_at'] = $document->created_at->locale('th_TH')->isoFormat('dddd DD MMMM YYYY');
      $data['created_by'] = $document->created_by;
      $data['updated_at'] = $document->updated_at->locale('th_TH')->isoFormat('dddd DD MMMM YYYY');
      $data['updated_by'] = $document->updated_by;
           

      Storage::makeDirectory($tmpDirectory);
      
      $currPrm = [
        'tmpDirectory' => $tmpDirectory,
      ];

      $templatePath = null;
      if ($document->Template != null && $document->Template->printTemplate!=null && Storage::exists($document->Template->printTemplate)) $templatePath = $document->Template->printTemplate;
      else if (Storage::exists('/default/documents/'.$document->templateCode.'.docx')) $templatePath = '/default/documents/'.$document->templateCode.'.docx';
      else if (Storage::exists('/default/documents/'.$document->templateCode.'.xlsx')) $templatePath = '/default/documents/'.$document->templateCode.'.xlsx';

      if ($templatePath!=null) {
        $TBS = new \clsTinyButStrong();
        $TBS->Plugin(\TBS_INSTALL, 'clsOpenTBS');
        $TBS->Plugin(clsMasterItem::class);
        $TBS->Plugin(clsPlugin::class);
        $TBS->NoErr = true;

        $TBS->LoadTemplate(storage_path('app/'.$templatePath),\OPENTBS_ALREADY_UTF8);
        self::merge($TBS,$data,$currPrm);

        $TBS->PlugIn(\OPENTBS_SELECT_HEADER, \OPENTBS_DEFAULT);
        self::merge($TBS,$data,$currPrm);

        $TBS->PlugIn(\OPENTBS_SELECT_FOOTER, \OPENTBS_DEFAULT);
        self::merge($TBS,$data,$currPrm);

        $TBS->Show(\OPENTBS_FILE,storage_path('app/'.$tmpFilename));

        Storage::copy($tmpFilename,'/documents/'.$document->hn.'/'.$document->templateCode.'/raw/'.$documentId.'_'.$tmpUniqId.'.docx');

        if (self::convertToPDF($tmpFilename,$tmpFilenamePDF)) {
          $returnData = Storage::get($tmpFilenamePDF);
        }
      }

      Storage::deleteDirectory($tmpDirectory);

      return $returnData;
    }


    public static function genericPrintDocumentBase64($documentId,$templateCode=null) {
      return base64_encode(self::genericPrintDocument($documentId,$templateCode=null));
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
