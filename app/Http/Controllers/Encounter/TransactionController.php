<?php

namespace App\Http\Controllers\Encounter;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DataController;
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

            $transactions->each(function($itemCollection,$key) use (&$hn,&$success,&$errorTexts,&$returnModels) {
                $item = collect($itemCollection->toArray())->map(function($row) {
                    return array_except($row,['insurance','encounter']);
                })->sortBy("transactionDateTime");

                $insurance =  \App\Models\Patient\PatientsInsurances::find($key);

                $detailInsurance = $item->groupBy('categoryInsurance');
                $detailCgd = $item->groupBy('categoryCgd');

                $summaryInsurance = $detailInsurance->map(function ($row,$key){
                    return [[
                        "categoryInsurance" => $key,
                        "totalPrice" => $row->sum('totalPrice'),
                        "totalDiscount" => $row->sum('totalDiscount'),
                        "finalPrice" => $row->sum('finalPrice'),
                    ]];
                })->flatten(1)->sortBy("categoryInsurance");
                $summaryCgd = $detailCgd->map(function ($row,$key){
                    return [[
                        "categoryCgd" => $key,
                        "totalPrice" => $row->sum('totalPrice'),
                        "totalDiscount" => $row->sum('totalDiscount'),
                        "finalPrice" => $row->sum('finalPrice'),
                    ]];
                })->flatten(1)->sortBy("categoryCgd");

                $detailInsurance = $detailInsurance->map(function ($row,$key){
                    return [[
                        "categoryInsurance" => $key,
                        "transactions" => $row
                    ]];
                })->flatten(1)->sortBy("categoryInsurance");

                $detailCgd = $detailCgd->map(function ($row,$key){
                    return [[
                        "categoryCgd" => $key,
                        "transactions" => $row
                    ]];
                })->flatten(1)->sortBy("categoryCgd");
                

                $invoiceData = [
                    "raw" => $item->toArray(),
                    "detailInsurance" => $detailInsurance->toArray(),
                    "detailCgd" => $detailCgd->toArray(),
                    "summaryInsurance" => $summaryInsurance->toArray(),
                    "summaryCgd" => $summaryCgd->toArray(),

                    "grandTotalPrice" => $item->sum('totalPrice'),
                    "grandTotalDiscount" => $item->sum('totalDiscount'),
                    "grandFinalPrice" => $item->sum('finalPrice'),

                    "insurance" => ($insurance) ? $insurance->toArray() : null,

                    "invoiceDateTime" => Carbon::now(),
                ];

                $invoice = new \App\Models\Accounting\AccountingInvoices();
                $invoice->hn = $hn;
                $invoice->patientsInsurancesId = is_numeric($key) ? $key : null;
                $invoice->amountDue = ($insurance && !$insurance->condition->isChargeToPatient) ? 0 : $invoiceData["grandFinalPrice"];
                $invoice->save();

                $invoiceDocument = DocumentController::addDocument($hn,env('INVOICE_TEMPLATE', 'invoice'),$invoiceData,env('INVOICE_CATEGORY', '999'),null,$invoice->invoiceId,'accounting');
                DocumentController::approveDocuments($invoiceDocument["returnModels"][0]->id);

                $invoice->documentId = $invoiceDocument["returnModels"][0]->id;
                $invoice->save();

                $itemCollection->each(function($itemTransaction,$key) use ($invoice) {
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
                $paymentData["invoiceId"] = $invoice->invoiceId;
                $paymentData["receiptDate"] = Carbon::now();
                $paymentData["cashiersPeriods"] = \App\Models\Accounting\CashiersPeriods::find($cashiersPeriodsId);

                $paymentData = array_merge($invoice->document->data,$paymentData);

                $receiptDocument = DocumentController::addDocument($invoice->hn,env('RECEIPT_TEMPLATE', 'receipt'),$paymentData,env('RECEIPT_CATEGORY', '999'),null,$payment->receiptId,'accounting');
                DocumentController::approveDocuments($receiptDocument["returnModels"][0]->id);

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

    public static function addTransactions($hn,$encounterId,$transactions) {
        data_fill($transactions,"*.hn",$hn);
        data_fill($transactions,"*.encounterId",$encounterId);

        $transactions = array_map(function ($value) {
            return array_except($value,'id');
        }, $transactions);
        
        $validationRule = [
          'hn' => 'required',
          'encounterId' => 'required',
          'productCode' => 'required',
        ];
        return DataController::createModel($transactions,\App\Models\Patient\PatientsTransactions::class,$validationRule);
    }
}
