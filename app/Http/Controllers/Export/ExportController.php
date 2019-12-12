<?php

namespace App\Http\Controllers\Export;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Master\MasterController;

class ExportController extends Controller
{
    public static function ExportProduct($afterDate=null) {
        if ($afterDate == null) $afterDate = \App\Models\Export\Icgoods::max('batch');
        if ($afterDate == null) $afterDate = 0;
        $batch = \Carbon\Carbon::now();

        $products = \App\Models\Master\Products::whereDate('updated_at','>',\Carbon\Carbon::parse($afterDate))->get();
        foreach($products as $product) {
            $icgood = new \App\Models\Export\Icgoods();
            $icgood->STKCOD = $product->productCode;
            $icgood->STKTH = substr($product->productName,0,50);
            $icgood->STKEN = substr($product->productNameEN,0,50);
            $icgood->QUCOD = ($product->saleUnit) ? $product->saleUnit : 'ea';
            $icgood->STKUNIT = ($product->saleUnit) ? $product->saleUnit : 'ea';
            $icgood->SELLPR1 = $product->price1;
            $icgood->SELLPR2 = $product->price2;
            $icgood->SELLPR3 = $product->price3;
            $icgood->SELLPR4 = $product->price4;
            $icgood->batch = $batch;
            $icgood->save();
        }

        return $batch;
    }

    public static function ExportPayer($afterDate=null) {
        if ($afterDate == null) $afterDate = \App\Models\Export\Emcuses::max('batch');
        if ($afterDate == null) $afterDate = 0;
        $batch = \Carbon\Carbon::now();

        $payers = \App\Models\Master\Payers::whereDate('updated_at','>',\Carbon\Carbon::parse($afterDate))->get();
        $patients = \App\Models\Patient\Patients::whereDate('updated_at','>',\Carbon\Carbon::parse($afterDate))->get();

        foreach ($patients as $patient) {
            $Emcus = \App\Models\Export\Emcuses();
            $Emcus->CUSCOD = $patient->hn;
            $Emcus->CUSTYP = "01";
            $Emcus->PRENAM = MasterController::translateMaster('$NamePrefix',$patient->name_real_th->namePrefix);
            $Emcus->CUSNAM = "";
            $Emcus->ADDR01 = "";
            $Emcus->ADDR02 = "";
            $Emcus->ADDR03 = "";
            $address = $patient->addresses->where('addressType','contact')->first();

            $Emcus->ZIPCOD = $address->postCode;
            $Emcus->TELNUM = "";
            $Emcus->TAXID = "";
            $Emcus->CONTACT = "";
            $Emcus->SHIPTO = "";
            $Emcus->batch = $batch;
            $Emcus->save();
        }

        foreach ($payers as $payer) {

        }
    }

    public static function ExportInvoice($afterDate=null) {
        if ($afterDate == null) $afterDate = \App\Models\Export\Icgoods::max('updated_at');
        $batch = \Carbon\Carbon::now();

    }

    public static function ExportPayment($afterDate=null) {

    }
}
