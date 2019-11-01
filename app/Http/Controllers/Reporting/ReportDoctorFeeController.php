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
                            ->whereDate('transactionDateTime',[$from,$to]);

        if ($invoiced) $transactions = $transactions->whereNotNull('invoiceId');

        $transactions = $transactions->with(['invoice'])->get();

        return $transactions;
    }
}
