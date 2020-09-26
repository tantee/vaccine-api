<?php

namespace App\Http\Controllers\Export;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class EclaimController extends Controller
{
    public static function ExportUcsOpd($backDate=7) {
        $localHospital = \App\Models\EclaimMaster\Hospitals::where('HMAIN',env('ECLAIM_HCODE','41711'))->first();
        $localProvince = ($localHospital) ? $localHospital->PROVINCE_ID : '';

        for($subDate=1;$subDate<=$backDate;$subDate++) {
            $batch = \Carbon\Carbon::now()->subDay($subDate)->endOfDay();
            $patients = \App\Models\Accounting\AccountingInvoices::eclaimUcs()->whereDate('created_at',$batch)->select('hn')->distinct()->get();
            
            foreach ($patients as $patient) {
                $invoices = \App\Models\Accounting\AccountingInvoices::eclaimUcs()->where('hn',$patient->hn)->whereDate('created_at',$batch)->get();
                $invoice = $invoices->firstWhere('payerCode','NHSO');

                if (!$invoice) $invoice = $invoices->first();
                
                $insurance = $invoice->insurance;

                $transactions = \App\Models\Patient\PatientsTransactions::whereIn('invoiceId',$invoices->pluck('invoiceId')->all())->get();

                $packageTransactions = \App\Models\Patient\PatientsTransactions::whereIn('invoiceId',$invoices->pluck('invoiceId')->all())->whereNotNull('itemizedProducts')->get();
                $pseudoTransactions = collect();
                foreach($packageTransactions as $packageTransaction) {
                    foreach(array_wrap($packageTransaction->itemizedProducts) as $product) {
                        $tmpTransaction = new \App\Models\Patient\PatientsTransactions();
                        $tmpTransaction->hn = $packageTransaction->hn;
                        $tmpTransaction->encounterId = $packageTransaction->encounterId;
                        $tmpTransaction->productCode = $product["productCode"];
                        $tmpTransaction->quantity = $product["quantity"];
                        $tmpTransaction->categoryCgd = null;
                        $tmpTransaction->transactionDateTime = $packageTransaction->transactionDateTime;
                        $tmpTransaction->performDoctorCode = $packageTransaction->performDoctorCode;
                        $tmpTransaction->soldPrice = $tmpTransaction->price;
                        $tmpTransaction->soldFinalPrice = $tmpTransaction->finalPrice;

                        $pseudoTransactions->push($tmpTransaction);
                    }
                }
                $hmainHospital = ($insurance->nhsoHCodeMain) ? $insurance->nhsoHCodeMain : $insurance->nhsoHCode;
                $hmainHospital = \App\Models\EclaimMaster\Hospitals::where('HMAIN',($hmainHospital) ? $hmainHospital : env('ECLAIM_HCODE','41711'))->first();
                $hmainProvince = ($hmainHospital) ? $hmainHospital->PROVINCE_ID : '';
                $sameProvince = ($localProvince == $hmainProvince);

                //clean up table before export
                \App\Models\Eclaim\INS::where('SEQ',$batch->format('ymd').$patient->hn)->delete();
                $ins = new \App\Models\Eclaim\INS();
                $ins->HN = $invoice->hn;
                $ins->INSCL = 'UCS';
                $ins->HOSPMAIN = ($insurance->nhsoHCodeMain) ? $insurance->nhsoHCodeMain : $insurance->nhsoHCode;
                if (!$ins->HOSPMAIN) $ins->HOSPMAIN = env('ECLAIM_HCODE','41711');
                $ins->SEQ = $batch->format('ymd').$patient->hn;
                $ins->batch = $batch;
                $ins->save();

                //clean up table before export
                \App\Models\Eclaim\PAT::where('HN',$invoice->hn)->delete();
                $pat = new \App\Models\Eclaim\PAT();
                $pat->HCODE =  env('ECLAIM_HCODE','41711');
                $pat->HN = $invoice->hn;

                $address = $invoice->patient->addresses()->where('addressType','census')->orderBy('updated_at','desc')->first();

                $pat->CHANGWAT = $address->province;
                $pat->AMPHUR = mb_substr($address->district,2,2);
                $pat->DOB = $invoice->patient->dateOfBirth->format('Ymd');;
                $pat->SEX = $invoice->patient->sex;
                $pat->MARRIAGE = ($invoice->patient->maritalStatus) ? $invoice->patient->maritalStatus : '9';
                $pat->OCCUPA = '000';
                $pat->NATION = '099';
                $pat->PERSON_ID = mb_substr($invoice->patient->personId,0,13);

                $name_th = $invoice->patient->name_real_th;
                $name = $name_th->firstName.' '.$name_th->lastName;
                $name_initial = \App\Http\Controllers\Master\MasterController::translateMaster('$NamePrefix',$name_th->namePrefix,false);
                if (mb_strlen($name)+mb_strlen($name_initial)>35) $name = mb_substr($name,0,35-mb_strlen($name_initial));

                $pat->NAMEPAT = $name.','.$name_initial;
                $pat->TITLE = $name_initial;
                $pat->FNAME = $name_th->firstName;
                $pat->LNAME = $name_th->lastName;
                $pat->IDTYPE = '1';
                $pat->save();

                //clean up table before export
                \App\Models\Eclaim\OPD::where('SEQ',$batch->format('ymd').$patient->hn)->delete();
                $opd = new \App\Models\Eclaim\OPD();
                $opd->HN = $invoice->hn;
                $opd->DATEOPD = $invoice->created_at->format('Ymd');
                $opd->TIMEOPD = $invoice->created_at->format('Hi');
                $opd->SEQ = $batch->format('ymd').$patient->hn;
                $opd->UUC = '1';
                $opd->save();

                //clean up table before export
                \App\Models\Eclaim\ORF::where('SEQ',$batch->format('ymd').$patient->hn)->delete();
                \App\Models\Eclaim\AER::where('SEQ',$batch->format('ymd').$patient->hn)->delete();
                if ($insurance->nhsoHCode!=null) {
                    $orf = new \App\Models\Eclaim\ORF();
                    $orf->HN = $invoice->hn;
                    $orf->DATEOPD = $invoice->created_at->format('Ymd');
                    $orf->REFER = $insurance->nhsoHCode;
                    $orf->REFERTYPE = '1';
                    $orf->SEQ = $batch->format('ymd').$patient->hn;
                    $orf->save();

                    $aer = new \App\Models\Eclaim\AER();
                    $aer->HN = $invoice->hn;
                    $aer->DATEOPD = $invoice->created_at->format('Ymd');
                    $aer->AUTHAE = '';
                    $aer->AEDATE = '';
                    $aer->AETIME = '';
                    $aer->AETYPE = '';
                    $aer->REFER_NO = ($insurance->contractNo) ? $insurance->contractNo : '';
                    $aer->REFMAINI = $insurance->nhsoHCode;
                    $aer->IREFTYPE = '0100';
                    $aer->REFMAINO = '';
                    $aer->OREFTYPE = '';
                    $aer->UCAE = ($sameProvince) ? '' : 'O';
                    $aer->EMTYPE = '';
                    $aer->SEQ = $batch->format('ymd').$patient->hn;
                    $aer->save();
                } 

                //clean up table before export
                \App\Models\Eclaim\OOP::where('SEQ',$batch->format('ymd').$patient->hn)->delete();

                $procedureTransactions = \App\Models\Patient\PatientsTransactions::whereIn('invoiceId',$invoices->pluck('invoiceId')->all())->whereHas('product',function ($query) {
                    $query->where('productType','procedure');
                    $query->whereNotNull('specification->icd9cm');
                })->get();

                $icd9cmLog = [];
                foreach ($procedureTransactions as $procedureTransaction) {
                    $doctorCode = ($procedureTransaction->performDoctor) ? $procedureTransaction->performDoctor->licenseNo : $procedureTransaction->order_doctor->licenseNo;
                    $icd9cms = array_wrap($procedureTransaction->product->specification['icd9cm']);

                    foreach($icd9cms as $icd9cm) {
                        if (!in_array($icd9cm,$icd9cmLog)) {
                            $oop = new \App\Models\Eclaim\OOP();
                            $oop->HN = $invoice->hn;
                            $oop->DATEOPD = $procedureTransaction->transactionDateTime->format('Ymd');
                            $oop->OPER = $icd9cm;
                            $oop->DROPID = ($doctorCode) ? $doctorCode : '';
                            $oop->PERSON_ID = $invoice->patient->personId;
                            $oop->SEQ = $batch->format('ymd').$patient->hn;
                            $oop->save();

                            $icd9cmLog[] = $icd9cm;
                        }
                    }
                }

                //clean up table before export
                \App\Models\Eclaim\CHT::where('SEQ',$batch->format('ymd').$patient->hn)->delete();
                $cht = new \App\Models\Eclaim\CHT();
                $cht->HN = $invoice->hn;
                $cht->DATE = $invoice->created_at->format('Ymd');
                $cht->TOTAL = $invoices->sum('amount');
                $cht->PAID = $invoices->sum('amountPaid');
                $cht->PERSON_ID = $invoice->patient->personId;
                $cht->SEQ = $batch->format('ymd').$patient->hn;
                $cht->save();

                //clean up table before export
                \App\Models\Eclaim\CHA::where('SEQ',$batch->format('ymd').$patient->hn)->delete();
                \App\Models\Eclaim\ADP::where('SEQ',$batch->format('ymd').$patient->hn)->delete();
                \App\Models\Eclaim\DRU::where('SEQ',$batch->format('ymd').$patient->hn)->delete();

                //List diagnosis first, insert later. Wait for auto add code
                $sumDiagnosis = [];
                $dxDoctorCode = null;

                foreach($transactions as $transaction) {
                    foreach ($transaction->encounter->diagnoses as $diagnosis) {
                        if (!isset($sumDiagnosis[$diagnosis->diagnosisType])) $sumDiagnosis[$diagnosis->diagnosisType] = [];
                        if (!isset($sumDiagnosis[$diagnosis->diagnosisType][$diagnosis->icd10])) $sumDiagnosis[$diagnosis->diagnosisType][$diagnosis->icd10] = ["count"=>0,"dateDx"=>null,"doctorCode"=>null];

                        $sumDiagnosis[$diagnosis->diagnosisType][$diagnosis->icd10]["count"] += 1;
                        $sumDiagnosis[$diagnosis->diagnosisType][$diagnosis->icd10]["doctorCode"] = ($transaction->encounter->doctor->licenseNo) ? $transaction->encounter->doctor->licenseNo : null;
                        $sumDiagnosis[$diagnosis->diagnosisType][$diagnosis->icd10]["dateDx"] = $transaction->encounter->admitDateTime->format('Ymd');

                        if ($transaction->encounter->doctor->licenseNo && ($dxDoctorCode == null)) $dxDoctorCode = $transaction->encounter->doctor->licenseNo;
                    }
                }

                if ($dxDoctorCode==null) {
                    $dxDoctorCode = ($transactions->first()->performDoctor) ? $transactions->first()->performDoctor->licenseNo : $transactions->first()->order_doctor->licenseNo;
                }

                $primaryDxMax = ["count"=>0,"dateDx"=>null,"doctorCode"=>null];
                $primaryDxIcd10 = '';

                if (isset($sumDiagnosis['primary'])) {
                    foreach($sumDiagnosis['primary'] as $tmpPrimaryIcd10 => $tmpPrimary) {
                        if ($tmpPrimary["count"]>$primaryDxMax["count"]) {
                            $primaryDxIcd10 = $tmpPrimaryIcd10;
                            $primaryDxMax = $tmpPrimary;
                        }
                    }
                } else {
                    $patientDx = $invoice->patient->diagnoses()->where('diagnosisType','primary')->orderBy('occurrence','desc')->first();
                    if ($patientDx != null) {
                        $primaryDxIcd10 = $patientDx->icd10;
                        $primaryDxMax['dateDx'] = $invoice->created_at->format('Ymd');
                        $primaryDxMax['doctorCode'] = $dxDoctorCode;
                    }
                }

                $adpTransactions = \App\Models\Patient\PatientsTransactions::whereIn('invoiceId',$invoices->pluck('invoiceId')->all())->whereHas('product',function ($query) {
                    $query->whereNotNull('eclaimAdpType');
                })->get();

                foreach($pseudoTransactions as $pseudoTransaction) {
                    if ($pseudoTransaction->product->eclaimAdpType != null) {
                        $adpTransactions->push($pseudoTransaction);
                    }
                }

                $nhsoCAGCode = '';
                $nshoMustAddZ510 = false;
                $nshoMustAddZ511 = false;

                if ($primaryDxIcd10!='') {
                    $icd10data = \App\Models\Master\MasterItems::where('groupKey','$ICD10')->where('itemCode',$primaryDxIcd10)->first();
                    if (isset($icd10data->properties['nhsoCAGCode'])) $nhsoCAGCode = $icd10data->properties['nhsoCAGCode'];
                }
                if ($nhsoCAGCode==null || $nhsoCAGCode=='') $nhsoCAGCode = $insurance->nhsoCAGCode;

                //OP Refer low cost
                if (!$sameProvince) {
                    $chaTransactions = \App\Models\Patient\PatientsTransactions::whereIn('invoiceId',$invoices->pluck('invoiceId')->all())->whereHas('product',function ($query) {
                        $query->whereNotNull('cgdCode')->whereNull('eclaimAdpType');
                        $query->orWhere('eclaimAdpType',6);
                    })->get();

                    foreach($pseudoTransactions as $pseudoTransaction) {
                        if (($pseudoTransaction->product->cgdCode != null && $pseudoTransaction->product->eclaimAdpType == null) || ($pseudoTransaction->product->eclaimAdpType == "6")) {
                            $chaTransactions->push($pseudoTransaction);
                        }
                    }

                    $summaryCgds = $chaTransactions->groupBy('categoryCgd');

                    foreach ($summaryCgds as $key=>$summaryCgd) {
                        $categoryCgd = \App\Models\Master\MasterItems::where('groupKey','$ProductCategoryCgd')->where('itemCode',$key)->first();
                        $eclaimChrgItem = $categoryCgd->properties['eclaimChrgItem'];

                        $cha = new \App\Models\Eclaim\CHA();
                        $cha->HN = $invoice->hn;
                        $cha->DATE = $invoice->created_at->format('Ymd');
                        $cha->CHRGITEM = ($eclaimChrgItem) ? $eclaimChrgItem : $key;
                        $cha->AMOUNT = $summaryCgd->sum('finalPrice');
                        $cha->PERSON_ID = $invoice->patient->personId;
                        $cha->SEQ = $batch->format('ymd').$patient->hn;
                        $cha->save();

                        //อวัยวะเทียม
                        if ($eclaimChrgItem=='21' || $eclaimChrgItem=='22') {

                        }

                        //ยา
                        if ($eclaimChrgItem=='31' || $eclaimChrgItem=='32' || $eclaimChrgItem=='41' || $eclaimChrgItem=='42') {
                            foreach ($summaryCgd as $item) {
                                $drug = \App\Models\EclaimMaster\DrugCatalogs::find($item->product->cgdCode);
                                if ($drug) {
                                    $dru = new \App\Models\Eclaim\DRU();
                                    $dru->HCODE = env('ECLAIM_HCODE','41711');
                                    $dru->HN = $item->hn;
                                    $dru->AN = ($item->encounter->encounterType=="IMP") ? $item->encounterId : '';
                                    $dru->CLINIC = '10';
                                    $dru->PERSON_ID = $invoice->patient->personId;
                                    $dru->DATE_SERV = $item->transactionDateTime->format('Ymd');
                                    $dru->DID = $drug->HOSPDRUGCODE;
                                    $dru->DIDNAME = $drug->TRADENAME;
                                    $dru->AMOUNT = $item->quantity;
                                    $dru->DRUGPRIC = $item->soldPrice;
                                    $dru->DRUGCOST = '';
                                    $dru->DIDSTD = '';
                                    $dru->UNIT = '';
                                    $dru->UNIT_PACK = '';
                                    $dru->SEQ =  $batch->format('ymd').$patient->hn;
                                    $dru->DRUGREMARK = '';
                                    $dru->PA_NO = '';
                                    $dru->TOTCOPAY = 0;
                                    $dru->USE_STATUS = ($item->quantity>5) ? '2' : '1';
                                    $dru->TOTAL = $item->soldFinalPrice;
                                    $dru->SIGCODE = '';
                                    $dru->SIGTEXT = '';
                                    $dru->save();
                                }
                            }

                        }

                        //lab,xray,invesitgation
                        if ($eclaimChrgItem=='71' || $eclaimChrgItem=='72' || $eclaimChrgItem=='81' || $eclaimChrgItem=='82' || $eclaimChrgItem=='91' || $eclaimChrgItem=='92') {
                            foreach ($summaryCgd as $item) {
                                $adp = new \App\Models\Eclaim\ADP();
                                $adp->HN = $invoice->hn;
                                $adp->DATEOPD = $item->transactionDateTime->format('Ymd');
                                $adp->TYPE = '8';
                                $adp->CODE = $item->product->cgdCode;
                                $adp->QTY = $item->quantity;
                                $adp->RATE = $item->soldPrice;
                                $adp->SEQ = $batch->format('ymd').$patient->hn;
                                $adp->CAGCODE = $nhsoCAGCode;
                                $adp->TOTAL = $item->soldFinalPrice;
                                $adp->save();
                            }
                        }

                        //หัตถการ
                        if ($eclaimChrgItem=='B1' || $eclaimChrgItem=='B2') {

                        }

                        //ค่าบริการพยาบาล
                        if ($eclaimChrgItem=='C1' || $eclaimChrgItem=='C2') {
                            foreach ($summaryCgd as $item) {
                                $adp = new \App\Models\Eclaim\ADP();
                                $adp->HN = $invoice->hn;
                                $adp->DATEOPD = $item->transactionDateTime->format('Ymd');
                                $adp->TYPE = '8';
                                $adp->CODE = $item->product->cgdCode;
                                $adp->QTY = $item->quantity;
                                $adp->RATE = $item->soldPrice;
                                $adp->SEQ = $batch->format('ymd').$patient->hn;
                                $adp->CAGCODE = $nhsoCAGCode;
                                $adp->TOTAL = $item->soldFinalPrice;
                                $adp->save();
                            }
                        }

                        //รายการอื่นๆ
                        if ($eclaimChrgItem=='J1' || $eclaimChrgItem=='J2') {

                        }
                    }
                }

                //OP and OPR high
                foreach ($adpTransactions as $adpTransaction) {
                    $productEclaimCode = $adpTransaction->product->eclaimCode;
                    if ($nhsoCAGCode=="NonPr" || $nhsoCAGCode=="Gca" || $nhsoCAGCode=='' || $nhsoCAGCode==null) {
                        $productEclaimCode = str_replace('RTX','RTX216_',$productEclaimCode);
                    }

                    $unitPrice = $adpTransaction->soldPrice;
                    $finalPrice = $adpTransaction->soldFinalPrice;

                    if ($adpTransaction->product->eclaimAdpType=='7') {
                        $caradio = \App\Models\EclaimMaster\CARadios::active()->where('CAR_CLAIMCODE',$productEclaimCode)->first();
                        if ($caradio!=null) {
                            if ($unitPrice > $caradio->CAR_RATE) {
                                $unitPrice = $caradio->CAR_RATE;
                                $finalPrice = $unitPrice * $adpTransaction->quantity;
                            }
                        }
                    }

                    if ($adpTransaction->product->eclaimAdpType=='7') $nshoMustAddZ510 = true;
                    if ($adpTransaction->product->eclaimAdpType=='6') $nshoMustAddZ511 = true;

                    if (!empty($productEclaimCode)) {
                        $adp = new \App\Models\Eclaim\ADP();
                        $adp->HN = $invoice->hn;
                        $adp->DATEOPD = $adpTransaction->transactionDateTime->format('Ymd');
                        $adp->TYPE = $adpTransaction->product->eclaimAdpType;
                        $adp->CODE = $productEclaimCode;
                        $adp->QTY = $adpTransaction->quantity;
                        $adp->RATE = $unitPrice;
                        $adp->SEQ = $batch->format('ymd').$patient->hn;
                        $adp->CAGCODE = (($nhsoCAGCode=='' || $nhsoCAGCode==null) && ($adpTransaction->product->eclaimAdpType=='6' || $adpTransaction->product->eclaimAdpType=='7')) ? "Gca" : $nhsoCAGCode;
                        $adp->TOTAL = $finalPrice;
                        $adp->save();
                    }

                    if ($adpTransaction->product->eclaimAdpType=='7' && !empty($adpTransaction->product->cgdCode) && !$sameProvince) {
                        $adp = new \App\Models\Eclaim\ADP();
                        $adp->HN = $invoice->hn;
                        $adp->DATEOPD = $adpTransaction->transactionDateTime->format('Ymd');
                        $adp->TYPE = '8';
                        $adp->CODE = $adpTransaction->product->cgdCode;
                        $adp->QTY = $adpTransaction->quantity;
                        $adp->RATE = $unitPrice;
                        $adp->SEQ = $batch->format('ymd').$patient->hn;
                        $adp->CAGCODE = (($nhsoCAGCode=='' || $nhsoCAGCode==null) && ($adpTransaction->product->eclaimAdpType=='6' || $adpTransaction->product->eclaimAdpType=='7')) ? "Gca" : $nhsoCAGCode;
                        $adp->TOTAL = $finalPrice;
                        $adp->save();
                    }
                    
                    if ($adpTransaction->product->eclaimAdpType=='6' && !empty($adpTransaction->product->cgdCode) && $sameProvince) {
                        $drug = \App\Models\EclaimMaster\DrugCatalogs::find($adpTransaction->product->cgdCode);
                        if ($drug) {
                            $dru = new \App\Models\Eclaim\DRU();
                            $dru->HCODE = env('ECLAIM_HCODE','41711');
                            $dru->HN = $adpTransaction->hn;
                            $dru->AN = ($adpTransaction->encounter->encounterType=="IMP") ? $adpTransaction->encounterId : '';
                            $dru->CLINIC = '10';
                            $dru->PERSON_ID = $invoice->patient->personId;
                            $dru->DATE_SERV = $adpTransaction->transactionDateTime->format('Ymd');
                            $dru->DID = $drug->HOSPDRUGCODE;
                            $dru->DIDNAME = $drug->TRADENAME;
                            $dru->AMOUNT = $adpTransaction->quantity;
                            $dru->DRUGPRIC = $adpTransaction->soldPrice;
                            $dru->DRUGCOST = '';
                            $dru->DIDSTD = '';
                            $dru->UNIT = '';
                            $dru->UNIT_PACK = '';
                            $dru->SEQ =  $batch->format('ymd').$patient->hn;
                            $dru->DRUGREMARK = '';
                            $dru->PA_NO = '';
                            $dru->TOTCOPAY = 0;
                            $dru->USE_STATUS = ($adpTransaction->quantity>5) ? '2' : '1';
                            $dru->TOTAL = $adpTransaction->soldFinalPrice;
                            $dru->SIGCODE = '';
                            $dru->SIGTEXT = '';
                            $dru->save();
                        }
                    }
                }

                //clean up table before export
                \App\Models\Eclaim\ODX::where('SEQ',$batch->format('ymd').$patient->hn)->delete();

                //Auto add diagnosis and insert
                if ($nshoMustAddZ510) {
                    if (!isset($sumDiagnosis['comorbid'])) $sumDiagnosis['comorbid'] = [];
                    if (!isset($sumDiagnosis['comorbid']['Z510'])) {
                        $sumDiagnosis['comorbid']['Z510'] = [
                            "count" => 1,
                            "dateDx" => $invoice->created_at->format('Ymd'),
                            "doctorCode"=> $dxDoctorCode,
                        ];
                    }
                }

                if ($nshoMustAddZ511) {
                    if (!isset($sumDiagnosis['comorbid'])) $sumDiagnosis['comorbid'] = [];
                    if (!isset($sumDiagnosis['comorbid']['Z511'])) {
                        $sumDiagnosis['comorbid']['Z511'] = [
                            "count" => 1,
                            "dateDx" => $invoice->created_at->format('Ymd'),
                            "doctorCode"=> $dxDoctorCode,
                        ];
                    }

                    if (!in_array("9925",$icd9cmLog)) {
                        $oop = new \App\Models\Eclaim\OOP();
                        $oop->HN = $invoice->hn;
                        $oop->DATEOPD = $invoice->created_at->format('Ymd');
                        $oop->OPER = "9925";
                        $oop->DROPID = $dxDoctorCode;
                        $oop->PERSON_ID = $invoice->patient->personId;
                        $oop->SEQ = $batch->format('ymd').$patient->hn;
                        $oop->save();

                        $icd9cmLog[] = "9925";
                    }
                }
                
                $dxTypeCode = ["primary"=>'1',"comorbid"=>'2',"complication"=>'3',"others"=>'4',"external"=>'5'];

                if ($primaryDxIcd10!='') {
                    $odx = new \App\Models\Eclaim\ODX();
                    $odx->HN = $invoice->hn;
                    $odx->DATEDX = $primaryDxMax["dateDx"];
                    $odx->DIAG = $primaryDxIcd10;
                    $odx->DXTYPE = $dxTypeCode['primary'];
                    $odx->DRDX = ($primaryDxMax["doctorCode"]) ? $primaryDxMax["doctorCode"] : $dxDoctorCode;
                    $odx->PERSON_ID = $invoice->patient->personId;
                    $odx->SEQ = $batch->format('ymd').$patient->hn;
                    $odx->save();
                }

                foreach($sumDiagnosis as $dxType=>$diagnoses) {
                    foreach($diagnoses as $diagnosisIcd10=>$diagnosis) {
                        if ($diagnosisIcd10==$primaryDxIcd10) continue;
                        if ($dxType=='primary' && isset($sumDiagnosis['comorbid'][$diagnosisIcd10])) continue;

                        $odx = new \App\Models\Eclaim\ODX();
                        $odx->HN = $invoice->hn;
                        $odx->DATEDX = $diagnosis["dateDx"];
                        $odx->DIAG = $diagnosisIcd10;
                        $odx->DXTYPE = ($dxType=='primary') ? $dxTypeCode['comorbid'] : $dxTypeCode[$dxType];
                        $odx->DRDX = ($diagnosis["doctorCode"]) ? $diagnosis["doctorCode"] : $dxDoctorCode;
                        $odx->PERSON_ID = $invoice->patient->personId;
                        $odx->SEQ = $batch->format('ymd').$patient->hn;
                        $odx->save();
                    }
                }
                
            }
        }

        self::Export16Folder($backDate);
        self::Export16Folder($backDate,true);
    }

    public static function Export16Folder($backDate=7,$oprefer=false) {
        $outputDirectory = ($oprefer) ? 'exports/eclaim/oprefer' : 'exports/eclaim/op';
        Storage::makeDirectory($outputDirectory);

        $localHospital = \App\Models\EclaimMaster\Hospitals::where('HMAIN',env('ECLAIM_HCODE','41711'))->first();
        $localProvince = ($localHospital) ? $localHospital->PROVINCE_ID : '';

        for ($subDate=1; $subDate<=$backDate; $subDate++) {
            $exportDate = \Carbon\Carbon::now()->subDay($subDate)->endOfDay();

            $insData = [];
            $patData = [];
            $opdData = [];
            $orfData = [];
            $odxData = [];
            $oopData = [];
            $ipdData = [];
            $irfData = [];
            $idxData = [];
            $iopData = [];
            $chtData = [];
            $chaData = [];
            $aerData = [];
            $adpData = [];
            $lvdData = [];
            $druData = [];

            $inss =  \App\Models\Eclaim\INS::whereDate('batch',$exportDate)->get();
            foreach($inss as $ins) {

                $hmainHospital = \App\Models\EclaimMaster\Hospitals::where('HMAIN',($ins->HOSPMAIN) ? $ins->HOSPMAIN : env('ECLAIM_HCODE','41711'))->first();
                $hmainProvince = ($hmainHospital) ? $hmainHospital->PROVINCE_ID : '';
                $sameProvince = ($localProvince == $hmainProvince);

                if (($sameProvince && $oprefer) || (!$sameProvince && !$oprefer)) continue;
                
                $insItem = [
                    "HN" => $ins->HN,
                    "INSCL" => $ins->INSCL,
                    "SUBTYPE" => $ins->SUBTYPE,
                    "CID" => $ins->CID,
                    "DATEIN" => $ins->DATEIN,
                    "DATEEXP" => $ins->DATEEXP,
                    "HOSPMAIN" => $ins->HOSPMAIN,
                    "HOSPSUB" => $ins->HOSPSUB,
                    "GOVCODE" => $ins->GOVCODE,
                    "GOVNAME" => $ins->GOVNAME,
                    "PERMITNO" => $ins->PERMITNO,
                    "DOCNO" => $ins->DOCNO,
                    "OWNRPID" => $ins->OWNRPID,
                    "OWNNAME" => $ins->OWNNAME,
                    "AN" => $ins->AN,
                    "SEQ" => $ins->SEQ,
                    "SUBINSCL" => $ins->SUBINSCL,
                    "RELINSCL" => $ins->RELINSCL,
                    "HTYPE" => $ins->HTYPE,
                ];

                $insData[] = \implode('|',$insItem);

                $pats = \App\Models\Eclaim\PAT::where('HN',$ins->HN)->get();
                foreach($pats as $pat) {
                    $patItem = [
                        "HCODE" => $pat->HCODE,
                        "HN" => $pat->HN,
                        "CHANGWAT" => $pat->CHANGWAT,
                        "AMPHUR" => $pat->AMPHUR,
                        "DOB" => $pat->DOB,
                        "SEX" => $pat->SEX,
                        "MARRIAGE" => $pat->MARRIAGE,
                        "OCCUPA" => $pat->OCCUPA,
                        "NATION" => $pat->NATION,
                        "PERSON_ID" => $pat->PERSON_ID,
                        "NAMEPAT" => $pat->NAMEPAT,
                        "TITLE" => $pat->TITLE,
                        "FNAME" => $pat->FNAME,
                        "LNAME" => $pat->LNAME,
                        "IDTYPE" => $pat->IDTYPE,
                    ];

                    $patData[] = \implode('|',$patItem);
                }

                $opds = \App\Models\Eclaim\OPD::where('SEQ',$ins->SEQ)->get();
                foreach($opds as $opd) {
                    $opdItem = [
                        "HN" => $opd->HN,
                        "CLINIC" => $opd->CLINIC,
                        "DATEOPD" => $opd->DATEOPD,
                        "TIMEOPD" => $opd->TIMEOPD,
                        "SEQ" => $opd->SEQ,
                        "UUC" => $opd->UUC,
                    ];

                    $opdData[] = \implode('|',$opdItem);
                }
                
                $orfs = \App\Models\Eclaim\ORF::where('SEQ',$ins->SEQ)->get();
                foreach($orfs as $orf) {
                    $orfItem = [
                        "HN" => $orf->HN,
                        "DATEOPD" => $orf->DATEOPD,
                        "CLINIC" => $orf->CLINIC,
                        "REFER" => $orf->REFER,
                        "REFERTYPE" => $orf->REFERTYPE,
                        "SEQ" => $orf->SEQ,
                    ];

                    $orfData[] = \implode('|',$orfItem);
                }

                $odxs = \App\Models\Eclaim\ODX::where('SEQ',$ins->SEQ)->get();
                foreach($odxs as $odx) {
                    $odxItem = [
                        "HN" => $odx->HN,
                        "DATEDX" => $odx->DATEDX,
                        "CLINIC" => $odx->CLINIC,
                        "DIAG" => $odx->DIAG,
                        "DXTYPE" => $odx->DXTYPE,
                        "DRDX" => $odx->DRDX,
                        "PERSON_ID" => $odx->PERSON_ID,
                        "SEQ" => $odx->SEQ,
                    ];

                    $odxData[] = \implode('|',$odxItem);
                }

                $oops = \App\Models\Eclaim\OOP::where('SEQ',$ins->SEQ)->get();
                foreach($oops as $oop) {
                    $oopItem = [
                        "HN" => $oop->HN,
                        "DATEOPD" => $oop->DATEOPD,
                        "CLINIC" => $oop->CLINIC,
                        "OPER" => $oop->OPER,
                        "DROPID" => $oop->DROPID,
                        "PERSON_ID" => $oop->PERSON_ID,
                        "SEQ" => $oop->SEQ,
                    ];

                    $oopData[] = \implode('|',$oopItem);
                }

                $ipds = \App\Models\Eclaim\IPD::where('AN',$ins->SEQ)->get();
                foreach($ipds as $ipd) {
                    $ipdItem = [
                        "HN" => $ipd->HN,
                        "AN" => $ipd->AN,
                        "DATEADM" => $ipd->DATEADM,
                        "TIMEADM" => $ipd->TIMEADM,
                        "DATEDSC" => $ipd->DATEDSC,
                        "TIMEDSC" => $ipd->TIMEDSC,
                        "DISCHS" => $ipd->DISCHS,
                        "DISCHT" => $ipd->DISCHT,
                        "WARDDSC" => $ipd->WARDDSC,
                        "DEPT" => $ipd->DEPT,
                        "ADM_W" => $ipd->ADM_W,
                        "UUC" => $ipd->UUC,
                        "SVCTYPE" => $ipd->SVCTYPE,
                    ];

                    $ipdData[] = \implode('|',$ipdItem);
                }

                $irfs = \App\Models\Eclaim\IRF::where('AN',$ins->SEQ)->get();
                foreach($irfs as $irf) {
                    $irfItem = [
                        "AN" => $irf->AN,
                        "REFER" => $irf->REFER,
                        "REFERTYPE" => $irf->REFERTYPE,
                    ];

                    $irfData[] = \implode('|',$irfItem);
                }
                
                $idxs = \App\Models\Eclaim\IDX::where('AN',$ins->SEQ)->get();
                foreach($idxs as $idx) {
                    $idxItem = [
                        "AN" => $idx->AN,
                        "DIAG" => $idx->DIAG,
                        "DXTYPE" => $idx->DXTYPE,
                        "DRDX" => $idx->DRDX,
                    ];

                    $idxData[] = \implode('|',$idxItem);
                }
                
                $iops = \App\Models\Eclaim\IOP::where('AN',$ins->SEQ)->get();
                foreach($iops as $iop) {
                    $iopItem = [
                        "AN" => $iop->AN,
                        "OPER" => $iop->OPER,
                        "OPTYPE" => $iop->OPTYPE,
                        "DROPID" => $iop->DROPID,
                        "DATEIN" => $iop->DATEIN,
                        "TIMEIN" => $iop->TIMEIN,
                        "DATEOUT" => $iop->DATEOUT,
                        "TIMEOUT" => $iop->TIMEOUT,
                    ];

                    $iopData[] = \implode('|',$iopItem);
                }

                $chts = \App\Models\Eclaim\CHT::where('SEQ',$ins->SEQ)->get();
                foreach($chts as $cht) {
                    $chtItem = [
                        "HN" => $cht->HN,
                        "AN" => $cht->AN,
                        "DATE" => $cht->DATE,
                        "TOTAL" => $cht->TOTAL,
                        "PAID" => $cht->PAID,
                        "PTTYPE" => $cht->PTTYPE,
                        "PERSON_ID" => $cht->PERSON_ID,
                        "SEQ" => $cht->SEQ,
                    ];

                    $chtData[] = \implode('|',$chtItem);
                }

                $chas = \App\Models\Eclaim\CHA::where('SEQ',$ins->SEQ)->get();
                foreach($chas as $cha) {
                    $chaItem = [
                        "HN" => $cha->HN,
                        "AN" => $cha->AN,
                        "DATE" => $cha->DATE,
                        "CHRGITEM" => $cha->CHRGITEM,
                        "AMOUNT" => $cha->AMOUNT,
                        "PERSON_ID" => $cha->PERSON_ID,
                        "SEQ" => $cha->SEQ,
                    ];

                    $chaData[] = \implode('|',$chaItem);
                }

                $aers = \App\Models\Eclaim\AER::where('SEQ',$ins->SEQ)->get();
                foreach($aers as $aer) {
                    $aerItem = [
                        "HN" => $aer->HN,
                        "AN" => $aer->AN,
                        "DATEOPD" => $aer->DATEOPD,
                        "AUTHAE" => $aer->AUTHAE,
                        "AEDATE" => $aer->AEDATE,
                        "AETIME" => $aer->AETIME,
                        "AETYPE" => $aer->AETYPE,
                        "REFER_NO" => $aer->REFER_NO,
                        "REFMAINI" => $aer->REFMAINI,
                        "IREFTYPE" => $aer->IREFTYPE,
                        "REFMAINO" => $aer->REFMAINO,
                        "OREFTYPE" => $aer->OREFTYPE,
                        "UCAE" => $aer->UCAE,
                        "EMTYPE" => $aer->EMTYPE,
                        "SEQ" => $aer->SEQ,
                        "AESTATUS" => $aer->AESTATUS,
                        "DALERT" => $aer->DALERT,
                        "TALERT" => $aer->TALERT,
                    ];

                    $aerData[] = \implode('|',$aerItem);
                }

                $adps = \App\Models\Eclaim\ADP::where('SEQ',$ins->SEQ)->get();
                foreach($adps as $adp) {
                    $adpItem = [
                        "HN" => $adp->HN,
                        "AN" => $adp->AN,
                        "DATEOPD" => $adp->DATEOPD,
                        "TYPE" => $adp->TYPE,
                        "CODE" => $adp->CODE,
                        "QTY" => $adp->QTY,
                        "RATE" => $adp->RATE,
                        "SEQ" => $adp->SEQ,
                        "CAGCODE" => $adp->CAGCODE,
                        "DOSE" => $adp->DOSE,
                        "CA_TYPE" => $adp->CA_TYPE,
                        "SERIALNO" => $adp->SERIALNO,
                        "TOTCOPAY" => $adp->TOTCOPAY,
                        "USE_STATUS" => $adp->USE_STATUS,
                        "TOTAL" => $adp->TOTAL,
                        "QTYDAY" => $adp->QTYDAY,
                    ];

                    $adpData[] = \implode('|',$adpItem);
                }
                
                $lvds = \App\Models\Eclaim\LVD::where('AN',$ins->SEQ)->get();
                foreach($lvds as $lvd) {
                    $lvdItem = [
                        "SEQLVD" => $lvd->SEQLVD,
                        "AN" => $lvd->AN,
                        "DATEOUT" => $lvd->DATEOUT,
                        "TIMEOUT" => $lvd->TIMEOUT,
                        "DATEIN" => $lvd->DATEIN,
                        "TIMEIN" => $lvd->TIMEIN,
                        "QTYDAY" => $lvd->QTYDAY,
                    ];

                    $lvdData[] = \implode('|',$lvdItem);
                }
                
                $drus = \App\Models\Eclaim\DRU::where('SEQ',$ins->SEQ)->get();
                foreach($drus as $dru) {
                    $druItem = [
                        "HCODE" => $dru->HCODE,
                        "HN" => $dru->HN,
                        "AN" => $dru->AN,
                        "CLINIC" => $dru->CLINIC,
                        "PERSON_ID" => $dru->PERSON_ID,
                        "DATE_SERV" => $dru->DATE_SERV,
                        "DID" => $dru->DID,
                        "DIDNAME" => $dru->DIDNAME,
                        "AMOUNT" => $dru->AMOUNT,
                        "DRUGPRIC" => $dru->DRUGPRIC,
                        "DRUGCOST" => $dru->DRUGCOST,
                        "DIDSTD" => $dru->DIDSTD,
                        "UNIT" => $dru->UNIT,
                        "UNIT_PACK" => $dru->UNIT_PACK,
                        "SEQ" => $dru->SEQ,
                        "DRUGREMARK" => $dru->DRUGREMARK,
                        "PA_NO" => $dru->PA_NO,
                        "TOTCOPAY" => $dru->TOTCOPAY,
                        "USE_STATUS" => $dru->USE_STATUS,
                        "TOTAL" => $dru->TOTAL,
                        "SIGCODE" => $dru->SIGCODE,
                        "SIGTEXT" => $dru->SIGTEXT,
                    ];

                    $druData[] = \implode('|',$druItem);
                }

            }
            $insData = iconv("UTF-8","TIS-620",\implode(PHP_EOL,$insData));
            $patData = iconv("UTF-8","TIS-620",\implode(PHP_EOL,$patData));
            $opdData = iconv("UTF-8","TIS-620",\implode(PHP_EOL,$opdData));
            $orfData = iconv("UTF-8","TIS-620",\implode(PHP_EOL,$orfData));
            $odxData = iconv("UTF-8","TIS-620",\implode(PHP_EOL,$odxData));
            $oopData = iconv("UTF-8","TIS-620",\implode(PHP_EOL,$oopData));
            $ipdData = iconv("UTF-8","TIS-620",\implode(PHP_EOL,$ipdData));
            $irfData = iconv("UTF-8","TIS-620",\implode(PHP_EOL,$irfData));
            $idxData = iconv("UTF-8","TIS-620",\implode(PHP_EOL,$idxData));
            $iopData = iconv("UTF-8","TIS-620",\implode(PHP_EOL,$iopData));
            $chtData = iconv("UTF-8","TIS-620",\implode(PHP_EOL,$chtData));
            $chaData = iconv("UTF-8","TIS-620",\implode(PHP_EOL,$chaData));
            $aerData = iconv("UTF-8","TIS-620",\implode(PHP_EOL,$aerData));
            $adpData = iconv("UTF-8","TIS-620",\implode(PHP_EOL,$adpData));
            $lvdData = iconv("UTF-8","TIS-620",\implode(PHP_EOL,$lvdData));
            $druData = iconv("UTF-8","TIS-620",\implode(PHP_EOL,$druData));

            $exportOutputDir = $outputDirectory.'/'.$exportDate->format('Y-m-d');
            Storage::makeDirectory($exportOutputDir);
            Storage::put($exportOutputDir.'/'.'INS.txt', $insData);
            Storage::put($exportOutputDir.'/'.'PAT.txt', $patData);
            Storage::put($exportOutputDir.'/'.'OPD.txt', $opdData);
            Storage::put($exportOutputDir.'/'.'ORF.txt', $orfData);
            Storage::put($exportOutputDir.'/'.'ODX.txt', $odxData);
            Storage::put($exportOutputDir.'/'.'OOP.txt', $oopData);
            Storage::put($exportOutputDir.'/'.'IPD.txt', $ipdData);
            Storage::put($exportOutputDir.'/'.'IRF.txt', $irfData);
            Storage::put($exportOutputDir.'/'.'IDX.txt', $idxData);
            Storage::put($exportOutputDir.'/'.'IOP.txt', $iopData);
            Storage::put($exportOutputDir.'/'.'CHT.txt', $chtData);
            Storage::put($exportOutputDir.'/'.'CHA.txt', $chaData);
            Storage::put($exportOutputDir.'/'.'AER.txt', $aerData);
            Storage::put($exportOutputDir.'/'.'ADP.txt', $adpData);
            Storage::put($exportOutputDir.'/'.'LVD.txt', $lvdData);
            Storage::put($exportOutputDir.'/'.'DRU.txt', $druData);
        }
    }
}
