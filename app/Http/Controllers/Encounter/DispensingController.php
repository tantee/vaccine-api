<?php

namespace App\Http\Controllers\Encounter;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DataController;

class DispensingController extends Controller
{
    public static function dispenseEncounter($encounterId) {
        $dispensings = \App\Models\Registration\EncountersDispensings::where('encounterId',$encounterId)->get();
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
            $tmpDispensing = \App\Models\Registration\EncountersDispensings::find($dispensing['id']);
            if (!$tmpDispensing->transactionId && !$tmpDispensing->isNotCharge) {
                $transactions = \App\Http\Controllers\Encounter\TransactionController::addTransactions($tmpDispensing->encounter->hn,$tmpDispensing->encounter->encounterId,$tmpDispensing->toArray());
                if ($transactions["success"]) {
                    $tmpDispensing->transactionId = $transactions["returnModels"][0]->id;
                    $tmpDispensing->save();
                }
            }
            array_push($returnModels,$tmpDispensing);
        }

        return $returnModels;
    }

    public static function chargeDispensingAll($encounterId) {
        $returnModels = [];

        $dispensings = \App\Models\Registration\EncountersDispensings::where('encounterId',$encounterId)->get();
        foreach($dispensings as $dispensing) {
            if (!$dispensing->transactionId && !$dispensing->isNotCharge) {
                $dispensing->isNotCharge = false;
                $transactions = \App\Http\Controllers\Encounter\TransactionController::addTransactions($dispensing->encounter->hn,$dispensing->encounter->encounterId,$dispensing->toArray());
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
            $tmpDispensing = \App\Models\Registration\EncountersDispensings::find($dispensing['id']);
            if ($tmpDispensing->transaction && !$tmpDispensing->transaction->invoiceId) {
                $tmpDispensing->transaction->delete();
                $tmpDispensing->transactionId = null;
                $tmpDispensing->save();
            }
            array_push($returnModels,$tmpDispensing);
        }

        return $returnModels;
    }

    public static function unchargeDispensingAll($encounterId) {
        $returnModels = [];

        $dispensings = \App\Models\Registration\EncountersDispensings::where('encounterId',$encounterId)->get();
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
