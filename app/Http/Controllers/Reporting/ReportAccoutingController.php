<?php

namespace App\Http\Controllers\Reporting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ReportAccoutingController extends Controller
{
    public static function getHDUnitInvoice($beginDate,$endDate) {
        $returnData = [];

        $from = Carbon::parse($beginDate)->startOfDay()->toDateTimeString();
        $to = Carbon::parse($endDate)->endOfDay()->toDateTimeString();

        $invoices = \App\Models\Accounting\AccountingInvoices::Hemodialysis()->whereBetween('created_at',[$from,$to])->where('isVoid',false);

        foreach ($invoices as $invoice) {
            $returnItem = $invoice->toArray();
            foreach($invoice->transactions as $transaction) {
                $returnItem[$transaction->productCode] = $transaction->soldFinalPrice;
            }
            $returnData[] = $returnItem;
        }

        return  $returnData;
    }
}
