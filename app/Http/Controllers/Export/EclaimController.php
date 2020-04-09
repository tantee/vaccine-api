<?php

namespace App\Http\Controllers\Export;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class EclaimController extends Controller
{
    public static function ExportUcsOpd($afterDate=null) {
        if ($afterDate == null) $afterDate = \App\Models\Eclaim\INS::max('batch');
        if ($afterDate == null) $afterDate = 0;
        $batch = \Carbon\Carbon::now();

        $invoices = \App\Models\Accounting\AccountingInvoices::eclaimUcs()->where('created_at','>',$afterDate)->get();

        foreach($invoices as $invoice) {
            $transactions = $invoice->transactions;
            $insurance = $invoice->insurance;

            $ins = new \App\Models\Eclaim\INS();
            $ins->HN = $invoice->hn;
            $ins->INSCL = 'UCS';
            $ins->HOSPMAIN = env('ECLAIM_HCODE','41711');
            $ins->SEQ = $invoice->invoiceId;
            $ins->batch = $batch;
            $ins->save();

            $pat = new \App\Models\Eclaim\PAT();
            $pat->HCODE =  env('ECLAIM_HCODE','41711');
            $pat->HN = $invoice->hn;

            $address = $invoice->patient->addresses()->where('addressType','census')->orderBy('updated_at','desc')->first();

            $pat->CHANGWAT = $address->province;
            $pat->AMPHUR = mb_substr($address->district,2,2);
            $pat->DOB = $invoice->patient->dateOfBirth->format('dmY');;
            $pat->SEX = $invoice->patient->sex;
            $pat->MARRIAGE = ($invoice->patient->maritalStatus) ? $invoice->patient->maritalStatus : '9';
            $pat->OCCUPA = '000';
            $pat->NATION = '099';
            $pat->PERSON_ID = mb_substr($invoice->patient->personId,0,13);

            $name_th = $invoice->patient->name_real_th;
            $name = $name_th->firstName.' '.$name_th->lastName;
            $name_initial = \App\Http\Controllers\Master\MasterController::translateMaster('$NamePrefix',$name_th->namePrefix,false);
            if (mb_strlen($name)+mb_strlen($name_initial)>35) $name = mb_substr($name,0,35-mb_strlen($name_initial));

            $pat->NAMEPAT = $name.','.$name_initial;
            $pat->TITLE = $name_initial;
            $pat->FNAME = $name_th->firstName;
            $pat->LNAME = $name_th->lastName;
            $pat->IDTYPE = '1';
            $pat->save();

            $opd = new \App\Models\Eclaim\OPD();
            $opd->HN = $invoice->hn;
            $opd->DATEOPD = $invoice->created_at->format('dmY');
            $opd->TIMEOPD = $invoice->created_at->format('Hi');
            $opd->SEQ = $invoice->invoiceId;
            $opd->UUC = '1';
            $opd->save();

            if ($insurance->nhsoHCode!=null) {
                $orf = new \App\Models\Eclaim\ORF();
                $orf->HN = $invoice->hn;
                $orf->DATEOPD = $invoice->created_at->format('dmY');
                $orf->REFER = $insurance->nhsoHCode;
                $orf->REFERTYPE = '1';
                $orf->SEQ = $invoice->invoiceId;
                $orf->save();
            } 

            // $odx = new \App\Models\Eclaim\ODX();
            // $odx->HN = $invoice->hn;
            // $odx->DATEDX = '';
            // $odx->DIAG = '';
            // $odx->DXTYPE = '';
            // $odx->DRDX = '';
            // $odx->PERSON_ID = $invoice->patient->personId;
            // $odx->SEQ = $invoice->invoiceId;
            // $odx->save();

            // $oop = new \App\Models\Eclaim\OOP();
            // $oop->HN = $invoice->hn;
            // $oop->DATEOPD = '';
            // $oop->OPER = '';
            // $oop->DROPID = '';
            // $oop->PERSON_ID = $invoice->patient->personId;
            // $oop->SEQ = $invoice->invoiceId;
            // $oop->save();

            $cht = new \App\Models\Eclaim\CHT();
            $cht->HN = $invoice->hn;
            $cht->DATE = $invoice->created_at->format('dmY');
            $cht->TOTAL = $invoice->amount;
            $cht->PAID = $invoice->amountPaid;
            $cht->PERSON_ID = $invoice->patient->personId;
            $cht->SEQ = $invoice->invoiceId;
            $cht->save();

            $summaryCgds = $transactions->groupBy('categoryCgd');
            $summaryCgds = $summaryCgds->map(function ($row,$key){
                return [[
                    "categoryCgd" => $key,
                    "totalPrice" => $row->sum('totalPrice'),
                    "totalDiscount" => $row->sum('totalDiscount'),
                    "finalPrice" => $row->sum('finalPrice'),
                ]];
            })->flatten(1)->sortBy("categoryCgd");

            foreach ($summaryCgds as $summaryCgd) {
                $categoryCgd = \App\Models\Master\MasterItems::where('groupKey','$ProductCategoryCgd')->where('itemCode',$summaryCgd['categoryCgd'])->first();
                $eclaimChrgItem = $categoryCgd->properties['eclaimChrgItem'];

                $cha = new \App\Models\Eclaim\CHA();
                $cha->HN = $invoice->hn;
                $cha->DATE = $invoice->created_at->format('dmY');
                $cha->CHRGITEM = ($eclaimChrgItem) ? $eclaimChrgItem : $summaryCgd['categoryCgd'];
                $cha->AMOUNT = $summaryCgd['finalPrice'];
                $cha->PERSON_ID = $invoice->patient->personId;
                $cha->SEQ = $invoice->invoiceId;
                $cha->save();
            }

            $adpTransactions = $invoice->transactions()->whereHas('product',function ($query) {
                $query->whereNotNull('eclaimAdpType');
            })->get();

            foreach ($adpTransactions as $adpTransaction) {
                $productEclaimCode = $adpTransaction->product->eclaimCode;
                if ($insurance->nhsoCAGCode=="NonPr" || $insurance->nhsoCAGCode=="Gca") {
                    $productEclaimCode = str_replace('RTX','RTX216_',$productEclaimCode);
                }

                $adp = new \App\Models\Eclaim\ADP();
                $adp->HN = $invoice->hn;
                $adp->DATEOPD = $adpTransaction->transactionDateTime->format('dmY');
                $adp->TYPE = $adpTransaction->product->eclaimAdpType;
                $adp->CODE = $productEclaimCode;
                $adp->QTY = $adpTransaction->quantity;
                $adp->RATE = $adpTransaction->soldPrice;
                $adp->SEQ = $invoice->invoiceId;
                $adp->CAGCODE = $insurance->nhsoCAGCode;
                $adp->TOTAL = $adpTransaction->soldFinalPrice;
                $adp->save();
            }
        }
    }

    public static function Export16Folder($beginDate=null,$endDate=null) {
        $outputDirectory = 'exports';
        $outputFile = $outputDirectory.'/eclaim16folder_'.Carbon::now()->format('YmdHis').'.zip';

        Storage::makeDirectory($outputDirectory);

        $zip = new \ZipArchive();
        $res = $zip->open(storage_path('app/'.$outputFile), \ZipArchive::CREATE);

        if ($res) {
            $inss = ($formDate == null) ? \App\Models\Eclaim\INS::all() : \App\Models\Eclaim\INS::whereDate('created_at','>=',$beginDate)->whereDate('created_at','<=',($endDate == null) ? $beginDate : $endDate)->get();
            $insData = [];
            foreach($inss as $ins) {
                $insItem = [
                    "HN" => $ins->HN,
                    "INSCL" => $ins->INSCL,
                    "SUBTYPE" => $ins->SUBTYPE,
                    "CID" => $ins->CID,
                    "DATEIN" => $ins->DATEIN,
                    "DATEEXP" => $ins->DATEEXP,
                    "HOSPMAIN" => $ins->HOSPMAIN,
                    "HOSPSUB" => $ins->HOSPSUB,
                    "GOVCODE" => $ins->GOVCODE,
                    "GOVNAME" => $ins->GOVNAME,
                    "PERMITNO" => $ins->PERMITNO,
                    "DOCNO" => $ins->DOCNO,
                    "OWNRPID" => $ins->OWNRPID,
                    "OWNNAME" => $ins->OWNNAME,
                    "AN" => $ins->AN,
                    "SEQ" => $ins->SEQ,
                    "SUBINSCL" => $ins->SUBINSCL,
                    "RELINSCL" => $ins->RELINSCL,
                    "HTYPE" => $ins->HTYPE,
                ];

                $insData[] = \implode('|',$insItem);
            }
            $insData = \implode(PHP_EOL,$insData);
            $zip->addFromString('INS.txt', $insData);

            $pats = ($formDate == null) ? \App\Models\Eclaim\PAT::all() : \App\Models\Eclaim\PAT::whereDate('created_at','>=',$beginDate)->whereDate('created_at','<=',($endDate == null) ? $beginDate : $endDate)->get();
            $patData = [];
            foreach($pats as $pat) {
                $patItem = [
                    "id" => $pat->id,
                    "HCODE" => $pat->HCODE,
                    "HN" => $pat->HN,
                    "CHANGWAT" => $pat->CHANGWAT,
                    "AMPHUR" => $pat->AMPHUR,
                    "DOB" => $pat->DOB,
                    "SEX" => $pat->SEX,
                    "MARRIAGE" => $pat->MARRIAGE,
                    "OCCUPA" => $pat->OCCUPA,
                    "NATION" => $pat->NATION,
                    "PERSON_ID" => $pat->PERSON_ID,
                    "NAMEPAT" => $pat->NAMEPAT,
                    "TITLE" => $pat->TITLE,
                    "FNAME" => $pat->FNAME,
                    "LNAME" => $pat->LNAME,
                    "IDTYPE" => $pat->IDTYPE,
                ];

                $patData[] = \implode('|',$patItem);
            }
            $patData = \implode(PHP_EOL,$patData);
            $zip->addFromString('PAT.txt', $patData);

            $opds = ($formDate == null) ? \App\Models\Eclaim\OPD::all() : \App\Models\Eclaim\OPD::whereDate('created_at','>=',$beginDate)->whereDate('created_at','<=',($endDate == null) ? $beginDate : $endDate)->get();
            $opdData = [];
            foreach($opds as $opd) {
                $opdItem = [
                    "HN" => $opd->HN,
                    "CLINIC" => $opd->CLINIC,
                    "DATEOPD" => $opd->DATEOPD,
                    "TIMEOPD" => $opd->TIMEOPD,
                    "SEQ" => $opd->SEQ,
                    "UUC" => $opd->UUC,
                ];

                $opdData[] = \implode('|',$opdItem);
            }
            $opdData = \implode(PHP_EOL,$opdData);
            $zip->addFromString('OPD.txt', $opdData);

            $orfs = ($formDate == null) ? \App\Models\Eclaim\ORF::all() : \App\Models\Eclaim\ORF::whereDate('created_at','>=',$beginDate)->whereDate('created_at','<=',($endDate == null) ? $beginDate : $endDate)->get();
            $orfData = [];
            foreach($orfs as $orf) {
                $orfItem = [
                    "HN" => $orf->HN,
                    "DATEOPD" => $orf->DATEOPD,
                    "CLINIC" => $orf->CLINIC,
                    "REFER" => $orf->REFER,
                    "REFERTYPE" => $orf->REFERTYPE,
                    "SEQ" => $orf->SEQ,
                ];

                $orfData[] = \implode('|',$orfItem);
            }
            $orfData = \implode(PHP_EOL,$orfData);
            $zip->addFromString('ORF.txt', $orfData);

            $odxs = ($formDate == null) ? \App\Models\Eclaim\ODX::all() : \App\Models\Eclaim\ODX::whereDate('created_at','>=',$beginDate)->whereDate('created_at','<=',($endDate == null) ? $beginDate : $endDate)->get();
            $odxData = [];
            foreach($odxs as $odx) {
                $odxItem = [
                    "HN" => $odx->HN,
                    "DATEDX" => $odx->DATEDX,
                    "CLINIC" => $odx->CLINIC,
                    "DIAG" => $odx->DIAG,
                    "DXTYPE" => $odx->DXTYPE,
                    "DRDX" => $odx->DRDX,
                    "PERSON_ID" => $odx->PERSON_ID,
                    "SEQ" => $odx->SEQ,
                ];

                $odxData[] = \implode('|',$odxItem);
            }
            $odxData = \implode(PHP_EOL,$odxData);
            $zip->addFromString('ODX.txt', $odx);

            $oops = ($formDate == null) ? \App\Models\Eclaim\OOP::all() : \App\Models\Eclaim\OOP::whereDate('created_at','>=',$beginDate)->whereDate('created_at','<=',($endDate == null) ? $beginDate : $endDate)->get();
            $oopData = [];
            foreach($oops as $oop) {
                $oopItem = [
                    "HN" => $oop->HN,
                    "DATEOPD" => $oop->DATEOPD,
                    "CLINIC" => $oop->CLINIC,
                    "OPER" => $oop->OPER,
                    "DROPID" => $oop->DROPID,
                    "PERSON_ID" => $oop->PERSON_ID,
                    "SEQ" => $oop->SEQ,
                ];

                $oopData[] = \implode('|',$oopItem);
            }
            $oopData = \implode(PHP_EOL,$oopData);
            $zip->addFromString('OOP.txt', $oopData);

            $ipds = ($formDate == null) ? \App\Models\Eclaim\IPD::all() : \App\Models\Eclaim\IPD::whereDate('created_at','>=',$beginDate)->whereDate('created_at','<=',($endDate == null) ? $beginDate : $endDate)->get();
            $ipdData = [];
            foreach($ipds as $ipd) {
                $ipdItem = [
                    "HN" => $ipd->HN,
                    "AN" => $ipd->AN,
                    "DATEADM" => $ipd->DATEADM,
                    "TIMEADM" => $ipd->TIMEADM,
                    "DATEDSC" => $ipd->DATEDSC,
                    "TIMEDSC" => $ipd->TIMEDSC,
                    "DISCHS" => $ipd->DISCHS,
                    "DISCHT" => $ipd->DISCHT,
                    "WARDDSC" => $ipd->WARDDSC,
                    "DEPT" => $ipd->DEPT,
                    "ADM_W" => $ipd->ADM_W,
                    "UUC" => $ipd->UUC,
                    "SVCTYPE" => $ipd->SVCTYPE,
                ];

                $ipdData[] = \implode('|',$ipdItem);
            }
            $ipdData = \implode(PHP_EOL,$ipdData);
            $zip->addFromString('IPD.txt', $ipdData);


            $irfs = ($formDate == null) ? \App\Models\Eclaim\IRF::all() : \App\Models\Eclaim\IRF::whereDate('created_at','>=',$beginDate)->whereDate('created_at','<=',($endDate == null) ? $beginDate : $endDate)->get();
            $irfData = [];
            foreach($irfs as $irf) {
                $irfItem = [
                    "AN" => $irf->AN,
                    "REFER" => $irf->REFER,
                    "REFERTYPE" => $irf->REFERTYPE,
                ];

                $irfData[] = \implode('|',$irfItem);
            }
            $irfData = \implode(PHP_EOL,$irfData);
            $zip->addFromString('IRF.txt', $irfData);

            $idxs = ($formDate == null) ? \App\Models\Eclaim\IDX::all() : \App\Models\Eclaim\IDX::whereDate('created_at','>=',$beginDate)->whereDate('created_at','<=',($endDate == null) ? $beginDate : $endDate)->get();
            $idxData = [];
            foreach($idxs as $idx) {
                $idxItem = [
                    "AN" => $idx->AN,
                    "DIAG" => $idx->DIAG,
                    "DXTYPE" => $idx->DXTYPE,
                    "DRDX" => $idx->DRDX,
                ];

                $idxData[] = \implode('|',$idxItem);
            }
            $idxData = \implode(PHP_EOL,$idxData);
            $zip->addFromString('IDX.txt', $idxData);

            $iops = ($formDate == null) ? \App\Models\Eclaim\IOP::all() : \App\Models\Eclaim\IOP::whereDate('created_at','>=',$beginDate)->whereDate('created_at','<=',($endDate == null) ? $beginDate : $endDate)->get();
            $iopData = [];
            foreach($iops as $iop) {
                $iopItem = [
                    "AN" => $iop->AN,
                    "OPER" => $iop->OPER,
                    "OPTYPE" => $iop->OPTYPE,
                    "DROPID" => $iop->DROPID,
                    "DATEIN" => $iop->DATEIN,
                    "TIMEIN" => $iop->TIMEIN,
                    "DATEOUT" => $iop->DATEOUT,
                    "TIMEOUT" => $iop->TIMEOUT,
                ];

                $iopData[] = \implode('|',$iopItem);
            }
            $iopData = \implode(PHP_EOL,$iopData);
            $zip->addFromString('IOP.txt', $iopData);

            $chts = ($formDate == null) ? \App\Models\Eclaim\CHT::all() : \App\Models\Eclaim\CHT::whereDate('created_at','>=',$beginDate)->whereDate('created_at','<=',($endDate == null) ? $beginDate : $endDate)->get();
            $chtData = [];
            foreach($chts as $cht) {
                $chtItem = [
                    "HN" => $cht->HN,
                    "AN" => $cht->AN,
                    "DATE" => $cht->DATE,
                    "TOTAL" => $cht->TOTAL,
                    "PAID" => $cht->PAID,
                    "PTTYPE" => $cht->PTTYPE,
                    "PERSON_ID" => $cht->PERSON_ID,
                    "SEQ" => $cht->SEQ,
                ];

                $chtData[] = \implode('|',$chtItem);
            }
            $chtData = \implode(PHP_EOL,$chtData);
            $zip->addFromString('CHT.txt', $chtData);

            $chas = ($formDate == null) ? \App\Models\Eclaim\CHA::all() : \App\Models\Eclaim\CHA::whereDate('created_at','>=',$beginDate)->whereDate('created_at','<=',($endDate == null) ? $beginDate : $endDate)->get();
            $chaData = [];
            foreach($chas as $cha) {
                $chaItem = [
                    "HN" => $cha->HN,
                    "AN" => $cha->AN,
                    "DATE" => $cha->DATE,
                    "CHRGITEM" => $cha->CHRGITEM,
                    "AMOUNT" => $cha->AMOUNT,
                    "PERSON_ID" => $cha->PERSON_ID,
                    "SEQ" => $cha->SEQ,
                ];

                $chaData[] = \implode('|',$chaItem);
            }
            $chaData = \implode(PHP_EOL,$chaData);
            $zip->addFromString('CHA.txt', $chaData);

            $aers = ($formDate == null) ? \App\Models\Eclaim\AER::all() : \App\Models\Eclaim\AER::whereDate('created_at','>=',$beginDate)->whereDate('created_at','<=',($endDate == null) ? $beginDate : $endDate)->get();
            $aerData = [];
            foreach($aers as $aer) {
                $aerItem = [
                    "HN" => $aer->HN,
                    "AN" => $aer->AN,
                    "DATEOPD" => $aer->DATEOPD,
                    "AUTHAE" => $aer->AUTHAE,
                    "AEDATE" => $aer->AEDATE,
                    "AETIME" => $aer->AETIME,
                    "AETYPE" => $aer->AETYPE,
                    "REFER_NO" => $aer->REFER_NO,
                    "REFMAINI" => $aer->REFMAINI,
                    "IREFTYPE" => $aer->IREFTYPE,
                    "REFMAINO" => $aer->REFMAINO,
                    "OREFTYPE" => $aer->OREFTYPE,
                    "UCAE" => $aer->UCAE,
                    "EMTYPE" => $aer->EMTYPE,
                    "SEQ" => $aer->SEQ,
                    "AESTATUS" => $aer->AESTATUS,
                    "DALERT" => $aer->DALERT,
                    "TALERT" => $aer->TALERT,
                ];

                $aerData[] = \implode('|',$aerItem);
            }
            $aerData = \implode(PHP_EOL,$aerData);
            $zip->addFromString('AER.txt', $aerData);

            $adps = ($formDate == null) ? \App\Models\Eclaim\ADP::all() : \App\Models\Eclaim\ADP::whereDate('created_at','>=',$beginDate)->whereDate('created_at','<=',($endDate == null) ? $beginDate : $endDate)->get();
            $adpData = [];
            foreach($adps as $adp) {
                $adpItem = [
                    "HN" => $adp->HN,
                    "AN" => $adp->AN,
                    "DATEOPD" => $adp->DATEOPD,
                    "TYPE" => $adp->TYPE,
                    "CODE" => $adp->CODE,
                    "QTY" => $adp->QTY,
                    "RATE" => $adp->RATE,
                    "SEQ" => $adp->SEQ,
                    "CAGCODE" => $adp->CAGCODE,
                    "DOSE" => $adp->DOSE,
                    "CA_TYPE" => $adp->CA_TYPE,
                    "SERIALNO" => $adp->SERIALNO,
                    "TOTCOPAY" => $adp->TOTCOPAY,
                    "USE_STATUS" => $adp->USE_STATUS,
                    "TOTAL" => $adp->TOTAL,
                    "QTYDAY" => $adp->QTYDAY,
                ];

                $adpData[] = \implode('|',$adpItem);
            }
            $adpData = \implode(PHP_EOL,$adpData);
            $zip->addFromString('ADP.txt', $adpData);

            $lvds = ($formDate == null) ? \App\Models\Eclaim\LVD::all() : \App\Models\Eclaim\LVD::whereDate('created_at','>=',$beginDate)->whereDate('created_at','<=',($endDate == null) ? $beginDate : $endDate)->get();
            $lvdData = [];
            foreach($lvds as $lvd) {
                $lvdItem = [
                    "SEQLVD" => $lvd->SEQLVD,
                    "AN" => $lvd->AN,
                    "DATEOUT" => $lvd->DATEOUT,
                    "TIMEOUT" => $lvd->TIMEOUT,
                    "DATEIN" => $lvd->DATEIN,
                    "TIMEIN" => $lvd->TIMEIN,
                    "QTYDAY" => $lvd->QTYDAY,
                ];

                $lvdData[] = \implode('|',$lvdItem);
            }
            $lvdData = \implode(PHP_EOL,$lvdData);
            $zip->addFromString('LVD.txt', $lvdData);

            $drus = ($formDate == null) ? \App\Models\Eclaim\DRU::all() : \App\Models\Eclaim\DRU::whereDate('created_at','>=',$beginDate)->whereDate('created_at','<=',($endDate == null) ? $beginDate : $endDate)->get();
            $druData = [];
            foreach($drus as $dru) {
                $druItem = [
                    "HN" => $dru->HN,
                    "AN" => $dru->AN,
                    "CLINIC" => $dru->CLINIC,
                    "PERSON_ID" => $dru->PERSON_ID,
                    "DATE_SERV" => $dru->DATE_SERV,
                    "DID" => $dru->DID,
                    "DIDNAME" => $dru->DIDNAME,
                    "AMOUNT" => $dru->AMOUNT,
                    "DRUGPRIC" => $dru->DRUGPRIC,
                    "DRUGCOST" => $dru->DRUGCOST,
                    "DIDSTD" => $dru->DIDSTD,
                    "UNIT" => $dru->UNIT,
                    "UNIT_PACK" => $dru->UNIT_PACK,
                    "SEQ" => $dru->SEQ,
                    "DRUGREMARK" => $dru->DRUGREMARK,
                    "PA_NO" => $dru->PA_NO,
                    "TOTCOPAY" => $dru->TOTCOPAY,
                    "USE_STATUS" => $dru->USE_STATUS,
                    "TOTAL" => $dru->TOTAL,
                    "SIGCODE" => $dru->SIGCODE,
                    "SIGTEXT" => $dru->SIGTEXT,
                ];

                $druData[] = \implode('|',$druItem);
            }
            $druData = \implode(PHP_EOL,$druData);
            $zip->addFromString('DRU.txt', $druData);
            $zip->close();

            $returnData = Storage::get($outputFile);

            return base64_encode($returnData);
        } else {
            return null;
        }
    }
}
