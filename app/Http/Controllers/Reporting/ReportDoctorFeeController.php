<?php

namespace App\Http\Controllers\Reporting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class ReportDoctorFeeController extends Controller
{
    public static function getDoctorFee($beginDate,$endDate,$invoiced=true,$doctor=false) {

        $from = Carbon::parse($beginDate)->startOfDay()->toDateTimeString();
        $to = Carbon::parse($endDate)->endOfDay()->toDateTimeString();

        $transactions = \App\Models\Patient\PatientsTransactions::where('productCode','LIKE','DF%')
                            ->whereBetween('transactionDateTime',[$from,$to]);

        if ($invoiced) $transactions = $transactions->whereNotNull('invoiceId');

        $transactions = $transactions->with(['invoice'])->get();

        $transactions = $transactions->groupBy('doctor_fee_doctor_code');

        $transactions = $transactions->map(function ($row,$key){
            return [[
                "orderDoctor" => $key,
                "orderDoctorNameTH" => $row[0]->doctor_fee_doctor->nameTH,
                "transactions" => $row,
                "grandFinalPrice" => $row->sum('finalPrice'),
            ]];
        })->flatten(1)->sortBy("orderDoctor");

        return [
            "reportBeginDate" => $beginDate,
            "reportEndDate" => $endDate,
            "report" => $transactions->toArray()
        ];
    }
}
