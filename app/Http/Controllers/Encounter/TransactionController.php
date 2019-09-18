<?php

namespace App\Http\Controllers\Encounter;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Master\IdController;
use App\Http\Controllers\Document\DocumentController;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public static function createInvoice($hn,$transactions) {
        $success = true;
        $errorTexts = [];
        $returnModels = [];
        
        DB::beginTransaction();

        $transactionsIds = array_pluck($transactions,'id');
        $transactions = \App\Models\Patient\PatientsTransactions::whereIn('id',$transactionsIds)->whereNull('invoiceId')->where('isChargable',true)->sharedLock()->get();

        if ($transactions != null) {
            $transactions = $transactions->groupBy(function ($item, $key) {
                return ($item->insurance==null) ? null : $item->insurance->id;
            });

            $transactions->each(function($item,$key) use (&$hn,&$success,&$errorTexts,&$returnModels) {
                $item = collect($item->toArray())->map(function($row) {
                    return array_except($row,['insurance','encounter']);
                });

                $insurance =  \App\Models\Patient\PatientsInsurances::find($key);

                $detailInsurance = $item->groupBy('category_insurance');
                $detailCgd = $item->groupBy('category_cgd');

                $summaryInsurance = $detailInsurance->map(function ($row){
                    return [
                        "totalPrice" => $row->sum('total_price'),
                        "totalDiscount" => $row->sum('total_discount'),
                        "finalPrice" => $row->sum('final_price'),
                    ];
                });
                $summaryCgd = $detailCgd->map(function ($row){
                    return [
                        "totalPrice" => $row->sum('total_price'),
                        "totalDiscount" => $row->sum('total_discount'),
                        "finalPrice" => $row->sum('final_price'),
                    ];
                });

                $invoiceData = [
                    "raw" => $item->toArray(),
                    "detailInsurance" => $detailInsurance->toArray(),
                    "detailCgd" => $detailCgd->toArray(),
                    "summaryInsurance" => $summaryInsurance->toArray(),
                    "summaryCgd" => $summaryCgd->toArray(),

                    "grandTotalPrice" => $item->sum('total_price'),
                    "grandTotalDiscount" => $item->sum('total_discount'),
                    "grandFinalPrice" => $item->sum('final_price'),

                    "insurance" => ($insurance) ? $insurance->toArray() : null,

                    "invoiceDateTime" => Carbon::now(),
                ];

                $invoice = new \App\Models\Accounting\AccountingInvoices();
                $invoice->hn = $hn;
                $invoice->patientsInsurancesId = is_numeric($key) ? $key : null;
                $invoice->amountDue = $invoiceData["grandFinalPrice"];
                $invoice->save();

                $invoiceDocument = DocumentController::addDocument($hn,env('INVOICE_TEMPLATE', 'invoice'),$invoiceData,env('INVOICE_CATEGORY', '999'),null,$invoice->invoiceId,'accounting');

                $invoice->documentId = $invoiceDocument["returnModels"][0]->id;
                $invoice->save();

                $item->each(function($itemTransaction,$key) use ($invoiceId) {
                    $itemTransaction->update([
                        "invoiceId" => $invoice->invoiceId,
                        "soldPatientsInsurancesId" => ($itemTransaction->insurance) ? $itemTransaction->insurance->id : null,
                        "soldPrice" => $itemTransaction->price,
                        "soldDiscount" => $itemTransaction->discount,
                        "soldTotalPrice" => $itemTransaction->total_price,
                        "soldTotalDiscount" => $itemTransaction->total_discount,
                        "soldFinalPrice" => $itemTransaction->final_price,
                    ]);
                });

                array_push($returnModels,$invoice);
            });

            DB::commit();
        } else {
            DB::rollBack();

            $success = false;
            array_push($errorTexts,["errorText" => 'Transactions not found']);
        }
        
        return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function createPayment($cashiersPeriodsId,$invoiceId,$paymentMethod,$paymentDetail=null,$paymentAccount=null,$amountPaid) {
        $success = true;
        $errorTexts = [];
        $returnModels = [];

        $invoice = \App\Models\Accounting\AccountingInvoices::find($invoiceId);
        if ($invoice!=null) {
            if ($amountPaid>$invoice->amount_outstanding) {
                $success = false;
                array_push($errorTexts,["errorText" => 'Payment amount is over outstanding balance']);
            }
            if ($success) {
                $paymentData = [
                    "cashiersPeriodsId" => $cashiersPeriodsId,
                    "paymentMethod" => $paymentMethod,
                    "paymentDetail" => $paymentDetail,
                    "paymentAccount" => $paymentAccount,
                    "amountDue" => $invoice->amount_outstanding,
                    "amountPaid" => $amountPaid,
                ];
                $payment = $invoice->payments()->create($paymentData);

                $paymentData["amountOutstanding"] = $payment->amountDue - $payment->amountPaid;
                $paymentData["receiptDate"] = Carbon::now();
                $paymentData["cashiersPeriods"] = \App\Models\Accounting\CashiersPeriods::find(cashiersPeriodsId);

                $paymentData = array_merge($invoice->document->data,$paymentData);

                $receiptDocument = DocumentController::addDocument($hn,env('RECEIPT_TEMPLATE', 'receipt'),$paymentData,env('RECEIPT_CATEGORY', '999'),null,$payment->receiptId,'accounting');

                $payment->documentId = $receiptDocument["returnModels"][0]->id;
                $payment->save();

                array_push($returnModels,$payment);
            }

        } else {
            $success = false;
            array_push($errorTexts,["errorText" => 'Invoice not found']);
        }
        return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function createTransactionPayment($hn,$transactions,$cashiersPeriodsId,$paymentMethod,$paymentDetail=null,$paymentAccount=null,$amountPaid) {
        $success = true;
        $errorTexts = [];
        $returnModels = [];

        $invoice = self::createInvoice($hn,$transactions);
        if ($invoice["success"]) {
            if (count($invoice["returnModels"])==1) {
                return self::createPayment($cashiersPeriodsId,$invoice["returnModels"][0]->invoiceId,$paymentMethod,$paymentDetail,$paymentAccount,$amountPaid);
            } else if (count($invoice["returnModels"])>1) {
                $success = false;
                array_push($errorTexts,["errorText" => 'Transcations are split to multiple invoices']);
            } else {
                $success = false;
                array_push($errorTexts,["errorText" => 'No chargable transcations']);
            }
            return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
        } else {
            return $invoice;
        }
    }
}
