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
    public static function createInvoice($hn,$transactions,$cashiersPeriodsId=null) {
        $success = true;
        $errorTexts = [];
        $returnModels = [];
        
        DB::beginTransaction();

        $transactionsIds = array_pluck($transactions,'id');
        $transactions = \App\Models\Patient\PatientsTransactions::whereIn('id',$transactionsIds)->whereNull('invoiceId')->where('isChargable',true)->sharedLock()->get();

        if ($transactions != null) {
            $transactions = $transactions->groupBy(function ($item, $key) {
                return ($item->insurance['PatientsInsurances']==null) ? null : $item->insurance["PatientsInsurances"]->id;
            });

            $transactions->each(function($itemCollection,$key) use (&$hn,&$success,&$errorTexts,&$returnModels,$cashiersPeriodsId) {
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
                    $row = $row->map(function($row) {
                        return array_except($row,['invoiceId','soldPatientsInsurancesId','soldPrice','soldDiscount','soldTotalPrice','soldTotalDiscount','soldFinalPrice']);
                    });
                    return [[
                        "categoryInsurance" => $key,
                        "transactions" => $row
                    ]];
                })->flatten(1)->sortBy("categoryInsurance");

                $detailCgd = $detailCgd->map(function ($row,$key){
                    $row = $row->map(function($row) {
                        return array_except($row,['invoiceId','soldPatientsInsurancesId','soldPrice','soldDiscount','soldTotalPrice','soldTotalDiscount','soldFinalPrice']);
                    });
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
                $invoice->cashiersPeriodsId = $cashiersPeriodsId;
                $invoice->hn = $hn;
                $invoice->patientsInsurancesId = is_numeric($key) ? $key : null;
                $invoice->amount = $invoiceData["grandFinalPrice"];
                $invoice->amountDue = ($insurance && !$insurance->isChargeToPatient) ? 0 : $invoiceData["grandFinalPrice"];
                $invoice->save();

                $invoiceDocument = DocumentController::addDocument($hn,env('INVOICE_TEMPLATE', 'invoice'),$invoiceData,env('INVOICE_CATEGORY', '999'),null,$invoice->invoiceId,'accounting');
                DocumentController::approveDocuments($invoiceDocument["returnModels"][0]->id);

                $invoice->documentId = $invoiceDocument["returnModels"][0]->id;
                $invoice->save();

                $itemCollection->each(function($itemTransaction,$key) use ($invoice) {
                    $itemTransaction->update([
                        "invoiceId" => $invoice->invoiceId,
                        "soldPatientsInsurancesId" => ($itemTransaction->insurance && $itemTransaction->insurance["PatientsInsurances"]) ? $itemTransaction->insurance["PatientsInsurances"]->id : null,
                        "soldInsuranceCode" => ($itemTransaction->insurance && $itemTransaction->insurance["Policy"]) ? $itemTransaction->insurance["Policy"]->insuranceCode : null,
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

    public static function createPayment($cashiersPeriodsId,$invoiceId,$paymentMethod,$amountPaid,$paymentDetail=null,$paymentAccount=null) {
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
                $paymentData["receiptDateTime"] = Carbon::now();
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

    public static function createTransactionPayment($hn,$transactions,$cashiersPeriodsId,$paymentMethod,$amountPaid,$paymentDetail=null,$paymentAccount=null) {
        $success = true;
        $errorTexts = [];
        $returnModels = [];

        $invoice = self::createInvoice($hn,$transactions,$cashiersPeriodsId);
        if ($invoice["success"]) {
            if (count($invoice["returnModels"])==1) {
                return self::createPayment($cashiersPeriodsId,$invoice["returnModels"][0]->invoiceId,$paymentMethod,$amountPaid,$paymentDetail,$paymentAccount);
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

    public static function addTransactions($hn,$encounterId,$transactions,$parentTransactionId=null) {
        data_fill($transactions,"*.hn",$hn);
        data_fill($transactions,"*.encounterId",$encounterId);

        if ($parentTransactionId!==null) data_fill($transactions,"*.parentTransactionId",$parentTransactionId);

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

    public static function voidInvoice($invoiceId,$note,$isVoidCashiersPeriodsId) {
        $success = true;
        $errorTexts = [];
        $returnModels = [];

        $invoice = \App\Models\Accounting\AccountingInvoices::find($invoiceId);
        if ($invoice!=null) {
            if ($invoice->payments->where('isVoid',false)->count()>0) {
                $success = false;
                array_push($errorTexts,["errorText" => 'Cannot void paid invoice']);
            }
            if ($success) {
                $tmpDocumentData = $invoice->document->data;
                $tmpDocumentData["isVoid"] = true;
                $tmpDocumentData["note"] = $note;
                $invoice->document->data = $tmpDocumentData;
                $invoice->document->save();
                $invoice->isVoid = true;
                $invoice->isVoidDateTime = Carbon::now();
                $invoice->isVoidCashiersPeriodsId = $isVoidCashiersPeriodsId;
                $invoice->note = $note;
                $invoice->save();

                $invoice->transactions->each(function($itemTransaction,$key) {
                    $itemTransaction->update([
                        'invoiceId'=>null,
                        'soldPatientsInsurancesId'=>null,
                        'soldPrice'=>null,
                        'soldDiscount'=>null,
                        'soldTotalPrice'=>null,
                        'soldTotalDiscount'=>null,
                        'soldFinalPrice'=>null,
                        'isRevised'=>true,
                    ]);
                });

                array_push($returnModels,$invoice);
            }
        } else {
            $success = false;
            array_push($errorTexts,["errorText" => 'Invoice not found']);
        }
        return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function voidPayment($receiptId,$note,$isVoidCashiersPeriodsId) {
        $success = true;
        $errorTexts = [];
        $returnModels = [];

        $payments = \App\Models\Accounting\AccountingPayments::where('receiptId',$receiptId)->get();
        if ($payments->count() > 0) {
            foreach($payments as $payment) {
                $tmpDocumentData = $payment->document->data;
                $tmpDocumentData["isVoid"] = true;
                $tmpDocumentData["note"] = $note;
                $payment->document->data = $tmpDocumentData;
                $payment->document->save();
                $payment->isVoid = true;
                $payment->isVoidDateTime = Carbon::now();
                $payment->isVoidCashiersPeriodsId = $isVoidCashiersPeriodsId;
                $payment->note = $note;
                $payment->save();
            }
            $returnModels = $payments;
        } else {
            $success = false;
            array_push($errorTexts,["errorText" => 'Receipt not found']);
        }
        return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function rebuildInvoice($invoiceId) {
        $success = true;
        $errorTexts = [];
        $returnModels = [];

        $transactions = \App\Models\Patient\PatientsTransactions::where('invoiceId',$invoiceId)->get();

        if ($transactions != null) {
            $transactions = $transactions->groupBy(function ($item, $key) {
                return ($item->insurance['PatientsInsurances']==null) ? null : $item->insurance["PatientsInsurances"]->id;
            });

            $transactions->each(function($itemCollection,$key) use (&$hn,&$success,&$errorTexts,&$returnModels,$invoiceId) {
                $item = collect($itemCollection->toArray())->map(function($row) {
                    return array_except($row,['insurance','encounter']);
                })->sortBy("transactionDateTime");

                $insurance =  \App\Models\Patient\PatientsInsurances::find($key);

                $detailInsurance = $item->groupBy('categoryInsurance');
                $detailCgd = $item->groupBy('categoryCgd');
,
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
                    $row = $row->map(function($row) {
                        return array_except($row,['invoiceId','soldPatientsInsurancesId','soldPrice','soldDiscount','soldTotalPrice','soldTotalDiscount','soldFinalPrice']);
                    });
                    return [[
                        "categoryInsurance" => $key,
                        "transactions" => $row
                    ]];
                })->flatten(1)->sortBy("categoryInsurance");

                $detailCgd = $detailCgd->map(function ($row,$key){
                    $row = $row->map(function($row) {
                        return array_except($row,['invoiceId','soldPatientsInsurancesId','soldPrice','soldDiscount','soldTotalPrice','soldTotalDiscount','soldFinalPrice']);
                    });
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

                $invoice = \App\Models\Accounting\AccountingInvoices::find($invoiceId);

                $invoiceDocument = \App\Models\Document\Documents::find($invoice->documentId);
                $invoiceDocument->data = $invoiceData;
                $invoiceDocument->save();

                foreach($invoice->payments() as $payment) {
                    $payment->document->data = array_replace($payment->document->data,$invoiceData);
                    $payment->document->save();
                }

                array_push($returnModels,$invoice);
            });
        } else {
            $success = false;
            array_push($errorTexts,["errorText" => 'Transactions not found']);
        }

        return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }
}
