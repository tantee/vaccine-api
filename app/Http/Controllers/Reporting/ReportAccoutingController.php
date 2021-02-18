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

        $invoices = \App\Models\Accounting\AccountingInvoices::Hemodialysis()->whereBetween('created_at',[$from,$to])->with(['transactions','patient'])->get();

        foreach ($invoices as $invoice) {
            $returnItem = $invoice->toArray();
            unset($returnItem['transactions']);

            $detailCgd = $invoice->transactions->groupBy('categoryCgd');
            $$returnItem['summaryCgd'] = $detailCgd->map(function ($row,$key){
                return [[
                    "categoryCgd" => $key,
                    "totalPrice" => $row->sum('totalPrice'),
                    "totalDiscount" => $row->sum('totalDiscount'),
                    "finalPrice" => $row->sum('finalPrice'),
                ]];
            })->flatten(1)->sortBy("categoryCgd");

            $returnData[] = $returnItem;
        }

        return  $returnData;
    }
}
