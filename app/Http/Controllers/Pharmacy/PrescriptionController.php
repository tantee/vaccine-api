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
                        "directions" => $prescriptionItem["directions"],
                        "cautions" => $prescriptionItem["cautions"],
                        "prescriptionId" => $prescription->id,
                    ];
                }
            }

            return DataController::createModel($labels,\App\Models\Pharmacy\PrescriptionsLabels::class);
        } else {
            return ["success" => false, "errorTexts" => [["errorText"=>"Invalid prescription ID"]], "returnModels" => null];
        }
    }

    public static function dispensingFromLabels($prescriptionId,$stockId) {
        $dispensings = [];

        $prescription = \App\Models\Pharmacy\Prescriptions::find($prescriptionId);
        if ($prescription && $stockId) {
            foreach($prescription->labels as $label) {
                $dispensings[] = [
                    "productCode" => $label->productCode,
                    "quantity" => $label->quantity,
                    "prescriptionId" => $label->prescriptionId,
                    "stockId" => $stockId,
                ];
            }

            return DataController::createModel($dispensings,\App\Models\Pharmacy\PrescriptionsDispensings::class);
        } else {
            return ["success" => false, "errorTexts" => [["errorText"=>"Invalid prescription ID or stock ID"]], "returnModels" => null];
        }        
    }

    public static function dispensePrescription($prescriptionId) {
        $dispensings = \App\Models\Pharmacy\PrescriptionsDispensings::where('prescriptionId',$prescriptionId);
        foreach($dispensings as $dispensing) {
            if ($dispensing->status == 'prepared') {
                $dispensing->status = "dispensed";
                $dispensing->save();
            }
        }
        return $dispensings;
    }

    public static function chargeDispensing($data) {
        $returnModels = [];

        if (\App\Utilities\ArrayType::isAssociative($data)) $data = [$data];
        foreach($data as $dispensing) {
            $tmpDispensing = \App\Models\Pharmacy\PrescriptionsDispensings::find($dispensing['id']);
            if (!$tmpDispensing->transactionId) {
                $tmpDispensing->isNotCharge = false;
                $transactions = App\Http\Controllers\Encounter\TransactionController::addTransactions($tmpDispensing->prescription->hn,$tmpDispensing->prescription->encounterId,$tmpDispensing);
                if ($transactions["success"]) {
                    $tmpDispensing->transactionId = $transactions["returnModels"][0]->id;
                    $tmpDispensing->save();
                }
            }
            array_push($returnModels,$tmpDispensing);
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
}
