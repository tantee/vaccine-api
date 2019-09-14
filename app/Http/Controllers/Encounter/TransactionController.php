<?php

namespace App\Http\Controllers\Encounter;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Master\IdController;
use App\Http\Controllers\Document\DocumentController;

class TransactionController extends Controller
{
    public static function createInvoice($hn,$transactionIds) {
        $success = true;
        $errorTexts = [];
        $returnModels = [];
        
        DB::beginTransaction();

        $transactionsIds = array_pluck($transactionsIds,'id');
        $transactions = \App\Models\Patient\PatientsTransactions::whereIn('id',$transactionsIds)->whereNull('referenceId')->sharedLock()->get();

        if ($transactions != null) {
            $transactions = $transactions->groupBy(function ($item, $key) {
                return ($item->insurance==null) ? null : $item->insurance->id;
            });

            $transactions->each(function($item,$key) {
                $item = collect($item->toArray())->map(function($row) {
                    return array_except($row,['insurance','encounter']);
                });

                $insurance =  \App\Models\Patient\PatientsInsurances::find($key);

                $detailInsurance = $item->groupBy('category_insurance');
                $detailCgd = $item->groupBy('category_cgd');

                $summaryInsurance = $detailInsurance->map(function ($row){
                    return [
                        "totalPrice" => $row->sum('total_price'),
                        "discountPrice" => $row->sum('discount_price'),
                        "finalPrice" => $row->sum('final_price'),
                    ];
                });
                $summaryCgd = $detailCgd->map(function ($row){
                    return [
                        "totalPrice" => $row->sum('total_price'),
                        "discountPrice" => $row->sum('discount_price'),
                        "finalPrice" => $row->sum('final_price'),
                    ];
                });

                $invoice = [
                    "raw" => $item->toArray(),
                    "detailInsurance" => $detailInsurance->toArray(),
                    "detailCgd" => $detailCgd->toArray(),
                    "summaryInsurance" => $summaryInsurance->toArray(),
                    "summaryCgd" => $summaryCgd->toArray(),

                    "grandTotalPrice" => $item->sum('total_price'),
                    "grandDiscountPrice" => $item->sum('discount_price'),
                    "grandFinalPrice" => $item->sum('final_price'),

                    "insurance" => $insurance,
                ];

                $invoiceId = IdController::issueId('invoice',env('INVOICE_ID_FORMAT', '\I\N\Vym'),env('INVOICE_ID_DIGIT', 6));

                $item->each(function($itemTransaction,$key) use ($invoiceId) {
                    $itemTransaction->update([
                        "referenceId" => $invoiceId,
                        "soldPatientsInsurancesId" => $itemTransaction->insurance->id,
                        "soldPrice" => $itemTransaction->price,
                        "soldDiscount" => $itemTransaction->discount,
                        "soldTotalPrice" => $itemTransaction->total_price,
                        "soldDiscountPrice" => $itemTransaction->discount_price,
                        "soldFinalPrice" => $itemTransaction->final_price,
                    ]);
                });

                $invoiceDocument = DocumentController::addDocument($hn,env('INVOICE_TEMPLATE', 'invoice'),$invoice,env('INVOICE_CATEGORY', '999'),null,$invoiceId,'accounting');

                $returnModels = array_merge($returnModels,$invoiceDocument["returnModels"]);
            });

            DB::commit();
        } else {
            DB::rollBack();

            $success = false;
            array_push($errorTexts,["errorText" => 'Transactions not found']);

            return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
        }
    }

    public static function createReceipt($invoiceIds,$paymentDetail) {
        if (!is_array($invoiceIds)) $invoiceIds = [$invoiceIds];

        $invoice = \App\Models\Document\Documents::where('folder','accounting')
                    ->where('referenceId',$invoiceId)
                    ->where('templateCode',env('INVOICE_TEMPLATE', 'invoice'))
                    ->where('isScanned',false)
                    ->orderBy('updated_at','desc')->first();
        if ($invoice!=null) {

        }

    }

    public static function createPayment($hn,$encounterId,$transactionIds) {

    }

    public static function cancelInvoice($invoiceId) {

    }
}
