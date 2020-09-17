<?php

namespace App\Http\Controllers\Pharmacy;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DataController;

class PrescriptionController extends Controller
{
    public static function labelsFromPrescription($prescriptionId) {
        $labels = [];

        $prescription = \App\Models\Pharmacy\Prescriptions::find($prescriptionId);
        if ($prescription) {
            $prescriptionData = $prescription->document->data;
            if (is_array($prescriptionData) && $prescriptionData["prescriptionList"]) {
                foreach($prescriptionData["prescriptionList"] as $prescriptionItem) {
                    if ($prescriptionItem["item"]) $prescriptionItem = $prescriptionItem["item"];
                    $labels[] = [
                        "productCode" => $prescriptionItem["productCode"],
                        "quantity" => $prescriptionItem["quantity"],
                        "directions" => (isset($prescriptionItem["directions"])) ? $prescriptionItem["directions"] : null,
                        "cautions" => (isset($prescriptionItem["cautions"])) ? $prescriptionItem["cautions"] : null,
                        "prescriptionId" => $prescription->id,
                    ];
                }
            }

            return DataController::createModel($labels,\App\Models\Pharmacy\PrescriptionsLabels::class);
        } else {
            return ["success" => false, "errorTexts" => [["errorText"=>"Invalid prescription ID"]], "returnModels" => []];
        }
    }

    public static function dispensingFromLabels($prescriptionId,$stockId,$isTemporary=false) {
        $dispensings = [];

        $prescription = \App\Models\Pharmacy\Prescriptions::find($prescriptionId);
        if ($prescription && $stockId) {
            foreach($prescription->labels as $label) {
                $dispensings[] = [
                    "productCode" => $label->productCode,
                    "quantity" => $label->quantity,
                    "prescriptionId" => $label->prescriptionId,
                    "stockId" => $stockId,
                    "isTemporary" => $isTemporary,
                ];
            }

            return DataController::createModel($dispensings,\App\Models\Pharmacy\PrescriptionsDispensings::class);
        } else {
            return ["success" => false, "errorTexts" => [["errorText"=>"Invalid prescription ID or stock ID"]], "returnModels" => []];
        }        
    }

    public static function dispensingFromLabelsItems($labelsItems,$stockId,$isTemporary=false) {
        $dispensings = [];

        if ($stockId) {
            if (\App\Utilities\ArrayType::isAssociative($labelsItems)) $labelsItems = [$labelsItems];
            foreach($labelsItems as $label) {
                $tmpLabel = \App\Models\Pharmacy\PrescriptionsLabels::find($label['id']);

                $dispensings[] = [
                    "productCode" => $tmpLabel->productCode,
                    "quantity" => $tmpLabel->quantity,
                    "prescriptionId" => $tmpLabel->prescriptionId,
                    "stockId" => $stockId,
                    "isTemporary" => $isTemporary,
                ];
            }

            return DataController::createModel($dispensings,\App\Models\Pharmacy\PrescriptionsDispensings::class);
        } else {
            return ["success" => false, "errorTexts" => [["errorText"=>"Invalid stock ID"]], "returnModels" => []];
        }        
    }

    public static function dispensePrescription($prescriptionId) {
        $dispensings = \App\Models\Pharmacy\PrescriptionsDispensings::where('prescriptionId',$prescriptionId)->get();
        foreach($dispensings as $dispensing) {
            if ($dispensing->status == 'prepared') {
                $dispensing->status = "dispensed";
                $dispensing->save();
            }
        }
        return $dispensings;
    }

    public static function returnTemporary($item,$stockId) {
        if (isset($item['id']) && isset($item['stockId']) && $item['stockId']>10000) {
            $stockProduct = \App\Models\Stock\StocksProducts::find($item['id']);
            if ($stockProduct) {
                $stockCard = new \App\Models\Stock\StocksCards();
                $stockCard->cardType = "return";
                $stockCard->productCode = $stockProduct->productCode;
                $stockCard->stockFrom = $stockProduct->stockId;
                $stockCard->stockTo = $stockId;
                $stockCard->lotNo = $stockProduct->lotNo;
                $stockCard->expiryDate = $stockProduct->expiryDate;
                $stockCard->quantity = $stockProduct->quantity;
                $stockCard->hn = ($stockProduct->encounter) ? $stockProduct->encounter->hn : null;
                $stockCard->encounterId = $stockProduct->encounterId;
                $stockCard->save();

                return $stockCard;
            }
        } else {
            return ["success" => false, "errorTexts" => [["errorText"=>"Invalid item data"]], "returnModels" => []];
        }  
    }

    public static function adjustTemporary($item,$stockId) {
        if (isset($item['id']) && isset($item['stockId']) && $item['stockId']>10000) {
            $stockProduct = \App\Models\Stock\StocksProducts::find($item['id']);
            if ($stockProduct) {
                $stockCard = new \App\Models\Stock\StocksCards();
                $stockCard->cardType = "correction";
                $stockCard->productCode = $stockProduct->productCode;
                $stockCard->stockFrom = $stockProduct->stockId;
                $stockCard->lotNo = $stockProduct->lotNo;
                $stockCard->expiryDate = $stockProduct->expiryDate;
                $stockCard->quantity = $stockProduct->quantity;
                $stockCard->hn = ($stockProduct->encounter) ? $stockProduct->encounter->hn : null;
                $stockCard->encounterId = $stockProduct->encounterId;
                $stockCard->save();

                return $stockCard;
            }
        } else {
            return ["success" => false, "errorTexts" => [["errorText"=>"Invalid item data"]], "returnModels" => []];
        }  
    }

    public static function chargeDispensing($data) {
        $returnModels = [];

        if (\App\Utilities\ArrayType::isAssociative($data)) $data = [$data];
        foreach($data as $dispensing) {
            $tmpDispensing = \App\Models\Pharmacy\PrescriptionsDispensings::find($dispensing['id']);
            if (!$tmpDispensing->transactionId && !$tmpDispensing->isNotCharge) {
                $transactions = \App\Http\Controllers\Encounter\TransactionController::addTransactions($tmpDispensing->prescription->hn,$tmpDispensing->prescription->encounterId,$tmpDispensing->toArray());
                if ($transactions["success"]) {
                    $tmpDispensing->transactionId = $transactions["returnModels"][0]->id;
                    $tmpDispensing->save();
                }
            }
            array_push($returnModels,$tmpDispensing);
        }

        return $returnModels;
    }

    public static function chargeDispensingAll($prescriptionId) {
        $returnModels = [];

        $dispensings = \App\Models\Pharmacy\PrescriptionsDispensings::where('prescriptionId',$prescriptionId)->get();
        foreach($dispensings as $dispensing) {
            if (!$dispensing->transactionId && !$dispensing->isNotCharge) {
                $dispensing->isNotCharge = false;
                $transactions = \App\Http\Controllers\Encounter\TransactionController::addTransactions($dispensing->prescription->hn,$dispensing->prescription->encounterId,$dispensing->toArray());
                if ($transactions["success"]) {
                    $dispensing->transactionId = $transactions["returnModels"][0]->id;
                    $dispensing->save();
                }
            }
            array_push($returnModels,$dispensing);
        }

        return $returnModels;
    }

    public static function unchargeDispensing($data) {
        $returnModels = [];

        if (\App\Utilities\ArrayType::isAssociative($data)) $data = [$data];
        foreach($data as $dispensing) {
            $tmpDispensing = \App\Models\Pharmacy\PrescriptionsDispensings::find($dispensing['id']);
            if ($tmpDispensing->transaction && !$tmpDispensing->transaction->invoiceId) {
                $tmpDispensing->transaction->delete();
                $tmpDispensing->transactionId = null;
                $tmpDispensing->save();
            }
            array_push($returnModels,$tmpDispensing);
        }

        return $returnModels;
    }

    public static function unchargeDispensingAll($prescriptionId) {
        $returnModels = [];

        $dispensings = \App\Models\Pharmacy\PrescriptionsDispensings::where('prescriptionId',$prescriptionId)->get();
        foreach($dispensings as $dispensing) {
            if ($dispensing->transaction && !$dispensing->transaction->invoiceId) {
                $dispensing->transaction->delete();
                $dispensing->transactionId = null;
                $dispensing->save();
            }
            array_push($returnModels,$dispensing);
        }

        return $returnModels;
    }
}
