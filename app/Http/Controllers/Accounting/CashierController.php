<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Accounting\CashiersPeriods;
use Carbon\Carbon;

class CashierController extends Controller
{
    //
    public static function addCashierPeriod($cashierId,$initialCash) {
        $success = true;
        $errorTexts = [];
        $returnModels = [];

        if ($cashierId == null) {
            $success = false;
            array_push($errorTexts,["errorText" => 'No cashierId provided']);
            return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
        }

        $period = CashiersPeriods::where('cashierId',$cashierId)->active()->first();
        if ($period==null) {
            $period = new CashiersPeriods();
            $period->startDateTime = Carbon::now();
            $period->cashierId = $cashierId;
            $period->initialCash = $initialCash;
            $period->save();
        }
        $returnModels = $period;

        return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function getCashierPeriod($cashierId) {
        $success = true;
        $errorTexts = [];
        $returnModels = [];

        if ($cashierId == null) {
            $success = false;
            array_push($errorTexts,["errorText" => 'No cashierId provided']);
            return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
        }

        $period = CashiersPeriods::where('cashierId',$cashierId)->active()->first();
        if ($period==null) {
            $success = false;
            array_push($errorTexts,["errorText" => 'No active period for this cashierId']);
        } else {
            $returnModels = $period;
        }
        
        return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function closeCashierPeriod($cashierId,$finalCash) {
        $success = true;
        $errorTexts = [];
        $returnModels = [];

        if ($cashierId == null) {
            $success = false;
            array_push($errorTexts,["errorText" => 'No cashierId provided']);
            return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
        }

        $period = CashiersPeriods::where('cashierId',$cashierId)->active()->first();
        if ($period==null) {
            $success = false;
            array_push($errorTexts,["errorText" => 'No active period for this cashierId']);
            return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
        } else {
            $period->finalCash = $finalCash;
            $period->endDateTime = Carbon::now();
            $period->save();
            $returnModels = $period->with(['Payments','Invoices','voidPayments','voidInvoices']);;
        }

        return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }

    public static function getCashierPeriodSummary($id) {
        $success = true;
        $errorTexts = [];
        $returnModels = [];

        $cashierPeriod = CashiersPeriods::find($id);

        if ($cashierPeriod !== null) {
            $returnModels = $cashierPeriod->with(['Payments','Invoices','voidPayments','voidInvoices']);
        } else {
            $success = false;
            array_push($errorTexts,["errorText" => 'Cashier period not found']);
        }

        return ["success" => $success, "errorTexts" => $errorTexts, "returnModels" => $returnModels];
    }
}
