<?php

namespace App\Http\Controllers\Export;

use Log;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Master\MasterController;
use App\Http\Controllers\Master\IdController;

class ExportController extends Controller
{
    public static function ExportProduct($afterDate=null) {
        if ($afterDate == null) $afterDate = \App\Models\Export\Icgoods::max('batch');
        if ($afterDate == null) $afterDate = 0;
        $batch = \Carbon\Carbon::now();

        $products = \App\Models\Master\Products::where('updated_at','>',$afterDate)->get();
        foreach($products as $product) {
            $icgood = \App\Models\Export\Icgoods::firstOrNew(['STKCOD'=>$product->productCode]);
            $icgood->STKCOD = $product->productCode;
            $icgood->STKTH = mb_substr($product->productName,0,50);
            $icgood->STKEN = mb_substr($product->productNameEN,0,50);
            $icgood->STKGRP = $product->category;
            $icgood->QUCOD = ($product->saleUnit) ? mb_substr($product->saleUnit,0,2) : 'ea';
            $icgood->STKUNIT = ($product->saleUnit) ? mb_substr($product->saleUnit,0,2) : 'ea';
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

        $payers = \App\Models\Master\Payers::where('updated_at','>',$afterDate)->get();
        $patients = \App\Models\Patient\Patients::where('updated_at','>',$afterDate)->get();

        foreach ($patients as $patient) {
            $Emcus = \App\Models\Export\Emcuses::firstOrNew(['CUSCOD'=>$patient->hn]);
            $Emcus->CUSCOD = $patient->hn;
            $Emcus->CUSTYP = "01";
            $Emcus->PRENAM = mb_substr(self::namePrefix($patient->name_real_th),0,15);
            $Emcus->CUSNAM = mb_substr(self::nameNoPrefix($patient->name_real_th),0,60);

            $address = $patient->addresses->where('addressType','contact')->first();
            $Emcus->ADDR01 = mb_substr(self::address1($address),0,50);
            $Emcus->ADDR02 = mb_substr(self::address2($address),0,50);
            $Emcus->ADDR03 = mb_substr(self::address3($address),0,30);

            $Emcus->ZIPCOD = mb_substr($address->postCode,0,5);
            $Emcus->TELNUM = $patient->primaryMobileNo;
            $Emcus->TAXID = $patient->personId;
            $Emcus->CONTACT = mb_substr($Emcus->CUSNAM,0,40);
            $Emcus->batch = $batch;
            $Emcus->save();
        }

        foreach ($payers as $payer) {
            $Emcus = \App\Models\Export\Emcuses::firstOrNew(['CUSCOD'=>$payer->payerCode]);
            $Emcus->CUSCOD = $payer->payerCode;
            $Emcus->CUSTYP = "02";
            $Emcus->PRENAM = "";
            $Emcus->CUSNAM = mb_substr($payer->payerName,0,60);
            $Emcus->ADDR01 = mb_substr($payer->payerAddress,0,50);
            $Emcus->TELNUM = $payer->payerTelephoneNo;
            $Emcus->TAXID = $payer->payerTaxNo;
            $Emcus->CONTACT = mb_substr($payer->payerName,0,40);
            $Emcus->SHIPTO = mb_substr($payer->payerAddress,0,10);
            $Emcus->batch = $batch;
            $Emcus->save();
        }

        return $batch->format('Y-m-d H:i:s');
    }
    
    public static function ExportInvoice($afterDate=null) {
        if ($afterDate == null) $afterDate = \App\Models\Export\Oeinvhs::max('batch');
        if ($afterDate == null) $afterDate = 0;
        $batch = \Carbon\Carbon::now();

        $invoices = \App\Models\Accounting\AccountingInvoices::where('updated_at','>',$afterDate)->get();

        foreach($invoices as $invoice) {
            //skip if CAH invoice
            if ($invoice->insurance!=null && $invoice->insurance->payerCode=='CAH') continue;

            $Oeinvh = \App\Models\Export\Oeinvhs::firstOrNew(['DOCNUM'=>$invoice->invoiceId]);
            $Oeinvh->DOCNUM = $invoice->invoiceId;
            $Oeinvh->DOCDAT = $invoice->created_at;
            $Oeinvh->DEPCOD = ($invoice->insurance!=null && $invoice->insurance->payerType!=null) ? $invoice->insurance->payerType : '99';
            //$Oeinvh->SLMCOD = $invoice->invoiceId;
            $Oeinvh->CUSCOD = ($invoice->insurance!=null && $invoice->insurance->payerCode!=null) ? $invoice->insurance->payerCode : $invoice->hn;
            $Oeinvh->YOUREF = mb_substr(self::name($invoice->patient->name_real_th)." ".$invoice->patient->hn,0,30);
            $Oeinvh->PAYTRM = ($invoice->insurance!=null && $invoice->insurance->payer!=null) ? $invoice->insurance->payer->creditPeriod : null;
            $Oeinvh->DUEDAT = (!empty($Oeinvh->PAYTRM)) ? $invoice->created_at->addDays($invoice->insurance->payer->creditPeriod)->format('dmY') : null;
            $Oeinvh->NXTSEQ = $invoice->transactions->count();
            $Oeinvh->AMOUNT = $invoice->amount;
            $Oeinvh->TOTAL = $invoice->amount;
            $Oeinvh->NETAMT = $invoice->amount;
            $Oeinvh->CUSNAM =  mb_substr(($invoice->insurance!=null && $invoice->insurance->payer!=null) ? $invoice->insurance->payer->payerName : self::name($invoice->patient->name_real_th),0,60);
            $Oeinvh->DOCSTAT = ($invoice->isVoid || ($invoice->insurance!=null && $invoice->insurance->payerCode=='CAH')) ? 'C' : 'N';
            $Oeinvh->batch = $batch;
            $Oeinvh->save();

            $seq = 1;
            $dispensings = [];
            
            \App\Models\Export\Oeinvls::where('DOCNUM',$invoice->invoiceId)->delete();

            foreach($invoice->transactions as $transaction) {
                $Oeinvl = new \App\Models\Export\Oeinvls();
                $Oeinvl->DOCNUM = $invoice->invoiceId;
                $Oeinvl->SEQNUM = str_pad($seq,3,'0',STR_PAD_LEFT);
                $Oeinvl->LOCCOD = $transaction->encounter->encounterType;
                $Oeinvl->STKCOD = $transaction->productCode;
                $Oeinvl->STKDES = mb_substr($transaction->product->productName,0,50);
                $Oeinvl->TRNQTY = $transaction->quantity;
                $Oeinvl->UNITPR = $transaction->price;
                $Oeinvl->TQUCOD = ($transaction->product->saleUnit) ? mb_substr($transaction->product->saleUnit,0,2) : 'ea';;
                $Oeinvl->DISC = ($transaction->discount==0) ? '-' : $transaction->discount.'%';
                $Oeinvl->DISCAMT = $transaction->total_discount;
                $Oeinvl->TRNVAL = $transaction->final_price;

                $Oeinvl->batch = $batch;
                $Oeinvl->save();

                if (($transaction->product->productType == "medicine" || $transaction->product->productType == "supply") && !$invoice->isVoid) {
                    $dispensings[] = [
                        "transactionDateTime" => $transaction->transactionDateTime,
                        "encounterType" => $transaction->encounter->encounterType,
                        "productCode" => $transaction->productCode,
                        "productName" => mb_substr($transaction->product->productName,0,50),
                        "quantity" => $transaction->quantity,
                        "invoiceId" => $invoice->invoiceId,
                    ];
                }

                if ($transaction->itemizedProducts != null && is_array($transaction->itemizedProducts) && !$invoice->isVoid) {
                    foreach($transaction->itemizedProducts as $itemizeProduct) {
                        $tmpProduct = \App\Models\Master\Products::find($itemizeProduct["productCode"]);
                        if ($tmpProduct->productType == "medicine" || $tmpProduct->productType=="supply") {
                            $dispensings[] = [
                                "transactionDateTime" => $transaction->transactionDateTime,
                                "encounterType" => $transaction->encounter->encounterType,
                                "productCode" => $tmpProduct->productCode,
                                "productName" => mb_substr($tmpProduct->productName,0,50),
                                "quantity" => $itemizeProduct["quantity"],
                                "invoiceId" => $invoice->invoiceId,
                            ];
                        }
                    }
                }

                $seq++;
            }

            \App\Models\Export\Oestkhs::where('REMARK',$invoice->invoiceId)->delete();
            \App\Models\Export\Oestkls::where('REMARK',$invoice->invoiceId)->delete();

            foreach($dispensings as $dispensing) {
                $Oestkh = new \App\Models\Export\Oestkhs();
                $Oestkh->DOCNUM = IdController::issueId('stock','y',8,'',false);
                $Oestkh->DOCDAT = $dispensing['transactionDateTime'];
                $Oestkh->DEPCOD = $dispensing['encounterType'];
                $Oestkh->REMARK = $dispensing['invoiceId'];
                $Oestkh->batch = $batch;
                $Oestkh->save();

                $Oestkl = new \App\Models\Export\Oestkls();
                $Oestkl->DOCNUM = $Oestkh->DOCNUM;
                $Oestkl->SEQNUM = '001';
                $Oestkl->LOCCOD = '01';
                $Oestkl->STKCOD = $dispensing['productCode'];
                $Oestkl->STKDES = $dispensing['productName'];
                $Oestkl->TRNQTY = $dispensing['quantity'];
                $Oestkl->REMARK = $dispensing['invoiceId'];
                $Oestkl->batch = $batch;
                $Oestkl->save();
            }
        }
        return $batch->format('Y-m-d H:i:s');
    }

    public static function ExportPayment($afterDate=null) {
        if ($afterDate == null) $afterDate = \App\Models\Export\Oerels::max('batch');
        if ($afterDate == null) $afterDate = 0;
        $batch = \Carbon\Carbon::now();

        $payments = \App\Models\Accounting\AccountingPayments::where('updated_at','>',$afterDate)->get();

        foreach($payments as $payment) {
            $Oerel = \App\Models\Export\Oerels::firstOrNew(['DOCNUM'=>$payment->receiptId]);
            $Oerel->DOCNUM = $payment->receiptId;
            $Oerel->DOCDAT = $payment->created_at;
            $Oerel->IVNUM = $payment->invoiceId;
            $Oerel->AMOUNT = $payment->amountPaid;
            $Oerel->PAYTYP = $payment->paymentMethod;
            $Oerel->PAYNOTE = $payment->paymentDetail;
            $Oerel->batch = $batch;
            $Oerel->save();
        }

        return $batch->format('Y-m-d H:i:s');
    }

    public static function ExportInvoiceId($invoiceId) {
        $batch = \App\Models\Export\Oeinvhs::max('batch');

        $invoices = \App\Models\Accounting\AccountingInvoices::where('invoiceId',$invoiceId)->get();

        foreach($invoices as $invoice) {
            //skip if CAH invoice
            if ($invoice->insurance!=null && $invoice->insurance->payerCode=='CAH') continue;
            //Do not autoexport if transaction count > 999
            if ($invoice->transactions->count() > 999) continue;

            $Oeinvh = \App\Models\Export\Oeinvhs::firstOrNew(['DOCNUM'=>$invoice->invoiceId]);
            $Oeinvh->DOCNUM = $invoice->invoiceId;
            $Oeinvh->DOCDAT = $invoice->created_at;
            $Oeinvh->DEPCOD = ($invoice->insurance!=null && $invoice->insurance->payerType!=null) ? $invoice->insurance->payerType : '99';
            //$Oeinvh->SLMCOD = $invoice->invoiceId;
            $Oeinvh->CUSCOD = ($invoice->insurance!=null && $invoice->insurance->payerCode!=null) ? $invoice->insurance->payerCode : $invoice->hn;
            $Oeinvh->YOUREF = mb_substr(self::name($invoice->patient->name_real_th)." ".$invoice->patient->hn,0,30);
            $Oeinvh->PAYTRM = ($invoice->insurance!=null && $invoice->insurance->payer!=null) ? $invoice->insurance->payer->creditPeriod : null;
            $Oeinvh->DUEDAT = (!empty($Oeinvh->PAYTRM)) ? $invoice->created_at->addDays($invoice->insurance->payer->creditPeriod)->format('dmY') : null;
            $Oeinvh->NXTSEQ = $invoice->transactions->count();
            $Oeinvh->AMOUNT = $invoice->amount;
            $Oeinvh->TOTAL = $invoice->amount;
            $Oeinvh->NETAMT = $invoice->amount;
            $Oeinvh->CUSNAM =  mb_substr(($invoice->insurance!=null && $invoice->insurance->payer!=null) ? $invoice->insurance->payer->payerName : self::name($invoice->patient->name_real_th),0,60);
            $Oeinvh->DOCSTAT = ($invoice->isVoid || ($invoice->insurance!=null && $invoice->insurance->payerCode=='CAH')) ? 'C' : 'N';
            $Oeinvh->batch = $batch;
            $Oeinvh->save();

            $seq = 1;
            $dispensings = [];

            \App\Models\Export\Oeinvls::where('DOCNUM',$invoice->invoiceId)->delete();

            foreach($invoice->transactions as $transaction) {
                $Oeinvl = new \App\Models\Export\Oeinvls();
                $Oeinvl->DOCNUM = $invoice->invoiceId;
                $Oeinvl->SEQNUM = str_pad($seq,3,'0',STR_PAD_LEFT);
                $Oeinvl->LOCCOD = $transaction->encounter->encounterType;
                $Oeinvl->STKCOD = $transaction->productCode;
                $Oeinvl->STKDES = mb_substr($transaction->product->productName,0,50);
                $Oeinvl->TRNQTY = $transaction->quantity;
                $Oeinvl->UNITPR = $transaction->price;
                $Oeinvl->TQUCOD = ($transaction->product->saleUnit) ? mb_substr($transaction->product->saleUnit,0,2) : 'ea';;
                $Oeinvl->DISC = ($transaction->discount==0) ? '-' : $transaction->discount.'%';
                $Oeinvl->DISCAMT = $transaction->total_discount;
                $Oeinvl->TRNVAL = $transaction->final_price;

                $Oeinvl->batch = $batch;
                $Oeinvl->save();

                if (($transaction->product->productType == "medicine" || $transaction->product->productType == "supply") && !$invoice->isVoid) {
                    $dispensings[] = [
                        "transactionDateTime" => $transaction->transactionDateTime,
                        "encounterType" => $transaction->encounter->encounterType,
                        "productCode" => $transaction->productCode,
                        "productName" => mb_substr($transaction->product->productName,0,50),
                        "quantity" => $transaction->quantity,
                        "invoiceId" => $invoice->invoiceId,
                    ];
                }

                if ($transaction->itemizedProducts != null && is_array($transaction->itemizedProducts) && !$invoice->isVoid) {
                    foreach($transaction->itemizedProducts as $itemizeProduct) {
                        $tmpProduct = \App\Models\Master\Products::find($itemizeProduct["productCode"]);
                        if ($tmpProduct->productType == "medicine" || $tmpProduct->productType=="supply") {
                            $dispensings[] = [
                                "transactionDateTime" => $transaction->transactionDateTime,
                                "encounterType" => $transaction->encounter->encounterType,
                                "productCode" => $tmpProduct->productCode,
                                "productName" => mb_substr($tmpProduct->productName,0,50),
                                "quantity" => $itemizeProduct["quantity"],
                                "invoiceId" => $invoice->invoiceId,
                            ];
                        }
                    }
                }

                $seq++;
            }

            \App\Models\Export\Oestkhs::where('REMARK',$invoice->invoiceId)->delete();
            \App\Models\Export\Oestkls::where('REMARK',$invoice->invoiceId)->delete();

            foreach($dispensings as $dispensing) {
                $Oestkh = new \App\Models\Export\Oestkhs();
                $Oestkh->DOCNUM = IdController::issueId('stock','y',8,'',false);
                $Oestkh->DOCDAT = $dispensing['transactionDateTime'];
                $Oestkh->DEPCOD = $dispensing['encounterType'];
                $Oestkh->REMARK = $dispensing['invoiceId'];
                $Oestkh->batch = $batch;
                $Oestkh->save();

                $Oestkl = new \App\Models\Export\Oestkls();
                $Oestkl->DOCNUM = $Oestkh->DOCNUM;
                $Oestkl->SEQNUM = '001';
                $Oestkl->LOCCOD = '01';
                $Oestkl->STKCOD = $dispensing['productCode'];
                $Oestkl->STKDES = $dispensing['productName'];
                $Oestkl->TRNQTY = $dispensing['quantity'];
                $Oestkl->REMARK = $dispensing['invoiceId'];
                $Oestkl->batch = $batch;
                $Oestkl->save();
            }
        }
        return $invoiceId;
    }

    public static function ExportPaymentId($receiptId) {
        $batch = \App\Models\Export\Oeinvhs::max('batch');

        $payments = \App\Models\Accounting\AccountingPayments::where('receiptId',$receiptId)->get();

        foreach($payments as $payment) {
            $Oerel = \App\Models\Export\Oerels::firstOrNew(['DOCNUM'=>$payment->receiptId]);
            $Oerel->DOCNUM = $payment->receiptId;
            $Oerel->DOCDAT = $payment->created_at;
            $Oerel->IVNUM = $payment->invoiceId;
            $Oerel->AMOUNT = $payment->amountPaid;
            $Oerel->PAYTYP = $payment->paymentMethod;
            $Oerel->PAYNOTE = $payment->paymentDetail;
            $Oerel->batch = $batch;
            $Oerel->save();
        }

        return $receiptId;
    }

    public static function Export($afterDate=null) {
        Log::info('Export to express begin');
        $output = [];

        $output[] = 'Exported Product '.self::ExportProduct($afterDate);
        $output[] = 'Exported Payer '.self::ExportPayer($afterDate);
        $output[] = 'Exported Invoice '.self::ExportInvoice($afterDate);
        $output[] = 'Exported Payment '.self::ExportPayment($afterDate);

        Log::info('Export to express finish');
        
        return implode("\n",$output);
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

        return implode(" ",$tmpAddress);
    }

    private static function address2($address) {
        $tmpAddress = [];

        $isThai = $address->country == "TH";

        if (!empty($address->subdistrict)) $tmpAddress[] = ($isThai) ? "ตำบล ".MasterController::translateMaster('$Subdistrict',$address->subdistrict) : $address->subdistrict;
        if (!empty($address->district)) $tmpAddress[] = ($isThai) ? "อำเภอ ".MasterController::translateMaster('$District',$address->district) : $address->district;

        return implode(" ",$tmpAddress);
    }

    private static function address3($address) {
        $tmpAddress = [];

        $isThai = $address->country == "TH";

        if (!empty($address->province)) $tmpAddress[] = ($isThai) ? "จังหวัด ".MasterController::translateMaster('$Province',$address->province) : $address->province;
        if (!empty($address->country) && !$isThai) $tmpAddress[] = "ประเทศ ".MasterController::translateMaster('$Country',$address->country);

        return implode(" ",$tmpAddress);
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

        return implode(" ",$tmpAddress);
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

        return implode(" ",$tmpName);
    }

    private static function name($name) {
        $tmpName = [];

        if (!empty($name->nameType) && ($name->nameType=='EN' || $name->nameType=='ALIAS_EN' )) $English = true;
        else $English = false;

        if (!empty($name->namePrefix)) $tmpName[] = MasterController::translateMaster('$NamePrefix',$name->namePrefix,$English);
        if (!empty($name->firstName)) $tmpName[] = $name->firstName;
        if (!empty($name->middleName)) $tmpName[] = $name->middleName;
        if (!empty($name->lastName)) $tmpName[] = $name->lastName;
        if (!empty($name->nameSuffix)) $tmpName[] = MasterController::translateMaster('$NameSuffix',$name->nameSuffix,$English);

        return implode(" ",$tmpName);
    }
}
