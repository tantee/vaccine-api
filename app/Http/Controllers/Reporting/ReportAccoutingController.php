<?php

namespace App\Http\Controllers\Reporting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class ReportAccoutingController extends Controller
{
    public static function getHDUnitInvoice($beginDate,$endDate) {
        $returnData = [];

        $from = Carbon::parse($beginDate)->startOfDay()->toDateTimeString();
        $to = Carbon::parse($endDate)->endOfDay()->toDateTimeString();

        $invoices = \App\Models\Accounting\AccountingInvoices::Hemodialysis()->whereBetween('created_at',[$from,$to])->with(['transaction','patient'])->get();

        foreach ($invoices as $invoice) {
            $returnItem = $invoice->toArray();
            unset($returnItem['transactions']);
            foreach($invoice->transactions as $transaction) {
                $returnItem[$transaction->productCode] = $transaction->soldFinalPrice;
            }
            $returnData[] = $returnItem;
        }

        return  $returnData;
    }
}
