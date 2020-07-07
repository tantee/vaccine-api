<?php

namespace App\Http\Controllers\Pharmacy;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PrescriptionController extends Controller
{
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
