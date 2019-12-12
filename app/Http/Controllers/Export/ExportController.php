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
            $icgood->STKTH = mb_substr($product->productName,0,50);
            $icgood->STKEN = mb_substr($product->productNameEN,0,50);
            $icgood->STKGRP = $product->category;
            $icgood->QUCOD = ($product->saleUnit) ? $product->saleUnit : 'ea';
            $icgood->STKUNIT = ($product->saleUnit) ? $product->saleUnit : 'ea';
            $icgood->SELLPR1 = $product->price1;
            $icgood->SELLPR2 = $product->price2;
            $icgood->SELLPR3 = $product->price3;
            $icgood->SELLPR4 = $product->price4;
            $icgood->STKTYP = ($product->productType == "Medicine" || $product->productType == "supply") ? "P" : "S";
            $icgood->batch = $batch;
            $icgood->save();
        }

        return $batch->format('Y-m-d H:i:s');
    }

    public static function ExportPayer($afterDate=null) {
        if ($afterDate == null) $afterDate = \App\Models\Export\Emcuses::max('batch');
        if ($afterDate == null) $afterDate = 0;
        $batch = \Carbon\Carbon::now();

        $payers = \App\Models\Master\Payers::whereDate('updated_at','>',\Carbon\Carbon::parse($afterDate))->get();
        $patients = \App\Models\Patient\Patients::whereDate('updated_at','>',\Carbon\Carbon::parse($afterDate))->get();

        foreach ($patients as $patient) {
            $Emcus = new \App\Models\Export\Emcuses();
            $Emcus->CUSCOD = $patient->hn;
            $Emcus->CUSTYP = "01";
            $Emcus->PRENAM = self::namePrefix($patient->name_real_th);
            $Emcus->CUSNAM = self::nameNoPrefix($patient->name_real_th);

            $address = $patient->addresses->where('addressType','contact')->first();
            $Emcus->ADDR01 = self::address1($address);
            $Emcus->ADDR02 = self::address2($address);
            $Emcus->ADDR03 = self::address3($address);

            $Emcus->ZIPCOD = $address->postCode;
            $Emcus->TELNUM = $patient->primaryMobileNo;
            $Emcus->TAXID = $patient->personId;
            $Emcus->CONTACT = $Emcus->CUSNAM;
            $Emcus->SHIPTO = self::address($address);
            $Emcus->batch = $batch;
            $Emcus->save();
        }

        foreach ($payers as $payer) {
            $Emcus = new \App\Models\Export\Emcuses();
            $Emcus->CUSCOD = $payer->payerCode;
            $Emcus->CUSTYP = "02";
            $Emcus->PRENAM = "";
            $Emcus->CUSNAM = $payer->payerName;
            $Emcus->ADDR01 = $payer->payerAddress;
            $Emcus->TELNUM = $payer->payerTelephoneNo;
            $Emcus->TAXID = $payer->payerTaxNo;
            $Emcus->CONTACT = $payer->payerName;
            $Emcus->SHIPTO = $payer->payerAddress;
            $Emcus->batch = $batch;
            $Emcus->save();
        }

        return $batch->format('Y-m-d H:i:s');
    }

    public static function ExportInvoice($afterDate=null) {
        if ($afterDate == null) $afterDate = \App\Models\Export\Icgoods::max('updated_at');
        $batch = \Carbon\Carbon::now();

    }

    public static function ExportPayment($afterDate=null) {

    }

    private static function address1($address) {
        $tmpAddress = [];

        $isThai = $address->country == "TH";

        if (!empty($address->address)) $tmpAddress[] = $address->address;
        if (!empty($address->village)) $tmpAddress[] = $address->village;
        if (!empty($address->moo)) $tmpAddress[] = $address->moo;
        if (!empty($address->trok)) $tmpAddress[] = $address->trok;
        if (!empty($address->soi)) $tmpAddress[] = $address->soi;
        if (!empty($address->street)) $tmpAddress[] = $address->street;

        $Value = implode(" ",$tmpAddress);
    }

    private static function address2($address) {
        $tmpAddress = [];

        $isThai = $address->country == "TH";

        if (!empty($address->subdistrict)) $tmpAddress[] = ($isThai) ? MasterController::translateMaster('$Subdistrict',$address->subdistrict) : $address->subdistrict;
        if (!empty($address->district)) $tmpAddress[] = ($isThai) ? MasterController::translateMaster('$District',$address->district) : $address->district;

        $Value = implode(" ",$tmpAddress);
    }

    private static function address3($address) {
        $tmpAddress = [];

        $isThai = $address->country == "TH";

        if (!empty($address->province)) $tmpAddress[] = ($isThai) ? MasterController::translateMaster('$Province',$address->province) : $address->province;
        if (!empty($address->country)) $tmpAddress[] = MasterController::translateMaster('$Country',$address->country);

        $Value = implode(" ",$tmpAddress);
    }

    private static function address($address) {
        $tmpAddress = [];

        $isThai = $address->country == "TH";

        if (!empty($address->address)) $tmpAddress[] = $address->address;
        if (!empty($address->village)) $tmpAddress[] = $address->village;
        if (!empty($address->moo)) $tmpAddress[] = $address->moo;
        if (!empty($address->trok)) $tmpAddress[] = $address->trok;
        if (!empty($address->soi)) $tmpAddress[] = $address->soi;
        if (!empty($address->street)) $tmpAddress[] = $address->street;
        if (!empty($address->subdistrict)) $tmpAddress[] = ($isThai) ? MasterController::translateMaster('$Subdistrict',$address->subdistrict) : $address->subdistrict;
        if (!empty($address->district)) $tmpAddress[] = ($isThai) ? MasterController::translateMaster('$District',$address->district) : $address->district;
        if (!empty($address->province)) $tmpAddress[] = ($isThai) ? MasterController::translateMaster('$Province',$address->province) : $address->province;
        if (!empty($address->country)) $tmpAddress[] = MasterController::translateMaster('$Country',$address->country);
        if (!empty($address->postCode)) $tmpAddress[] = $address->postCode;

        $Value = implode(" ",$tmpAddress);
    }

    private static function namePrefix($name) {
        if (!empty($name->nameType) && ($name->nameType=='EN' || $name->nameType=='ALIAS_EN' )) $English = true;
        else $English = false;

        return MasterController::translateMaster('$NamePrefix',$name->namePrefix,$English);
    }

    private static function nameNoPrefix($name) {
        $tmpName = [];

        if (!empty($name->nameType) && ($name->nameType=='EN' || $name->nameType=='ALIAS_EN' )) $English = true;
        else $English = false;

        if (!empty($name->firstName)) $tmpName[] = $name->firstName;
        if (!empty($name->middleName)) $tmpName[] = $name->middleName;
        if (!empty($name->lastName)) $tmpName[] = $name->lastName;
        if (!empty($name->nameSuffix)) $tmpName[] = MasterController::translateMaster('$NameSuffix',$name->nameSuffix,$English);

        $Value = implode(" ",$tmpName);
    }
}
