<?php

namespace App\Http\Controllers\Export;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

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
            $pat->MARRIAGE = $invoice->patient->maritalStatus;
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

            $orf = new \App\Models\Eclaim\ORF();
            $orf->HN = $invoice->hn;
            $orf->DATEOPD = $invoice->created_at->format('dmY');
            $orf->REFER = $insurance->nhsoHCode;
            $orf->REFERTYPE = '1';
            $orf->SEQ = $invoice->invoiceId;
            $orf->save();

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
            $cht->HN = $invoice->invoiceId;
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
                $categoryCgd = \App\Models\Master\MasterItems::where('groupKey','$ProductCategoryCgd')->where('itemCode',$summaryCgd['categoryCgd'])->get();
                $eclaimChrgItem = $categoryCgd->properties['eclaimChrgItem'];

                $cha = new \App\Models\Eclaim\CHA();
                $cha->HN = $invoice->hn;
                $cha->DATE = $invoice->created_at->format('dmY');
                $cha->CHRGITEM = $eclaimChrgItem;
                $cha->AMOUNT = $summaryCgd['finalPrice'];
                $cha->PERSON_ID = $invoice->patient->personId;
                $cha->SEQ = $invoice->invoiceId;
                $cha->save();
            }

            $adpTransactions = $transactions->whereHas('product',function (Builder $query) {
                $query->whereNotNull('nhsoAdpType');
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
}
