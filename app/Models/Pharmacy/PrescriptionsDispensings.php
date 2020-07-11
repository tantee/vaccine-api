<?php

namespace App\Models\Pharmacy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class PrescriptionsDispensings extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    public function Prescription() {
        return $this->belongsTo('App\Models\Pharmacy\Prescriptions','prescriptionId','id');
    }

    public function StocksCards() {
        return $this->hasMany('App\Models\Stock\StocksCards','prescriptionsDispensingId','id');
    }

    public function transaction() {
        return $this->hasOne('App\Models\Patient\PatientsTransactions','id','transactionId');
    }

    public static function boot() {
        static::saved(function($model) {
            $original = $model->getOriginal();
            if ($model->status == 'dispensed' && (!$original["status"] || $original["status"] != 'dispensed')) {
                $model->createStockCard();
            }
            if ($original["status"] && $original['status']=='dispensed' && $model->status!='dispensed') {
                $model->StocksCards()->delete();
            }
        });

        static::deleted(function($model) {
            $model->StocksCards->each->delete();
        });

        parent::boot();
    }

    private function createStockCard() {
        $qtyToDispense = $this->quantity;

        //create fixed lotNo
        if ($this->lotNo) {
            $existStocks = \App\Models\Stock\StocksProducts::where('stockId',$this->stockId)->where('productCode',$this->productCode)->where('lotNo',$this->lotNo)->where('quantity','>',0)->get();
            foreach($existStocks as $existStock) {
                $qtyStockDispense = ($existStock->quantity>=$qtyToDispense) ? $qtyToDispense : $existStock->quantity;
                if ($qtyStockDispense>0 && $this->createStockCardDispense($existStock->stockId,$existStock->lotNo,$existStock->expiryDate,$qtyStockDispense)) {
                    $qtyToDispense = $qtyToDispense - $qtyStockDispense;
                }
                if ($qtyToDispense==0) break;
            }
        }

        //create unspecific lotNo with expiry date
        if ($qtyToDispense>0) {
            $existStocks = \App\Models\Stock\StocksProducts::where('stockId',$this->stockId)->where('productCode',$this->productCode)->where('quantity','>',0)->whereNotNull('expiryDate')->orderBy('expiryDate')->get();
            foreach($existStocks as $existStock) {
                $qtyStockDispense = ($existStock->quantity>=$qtyToDispense) ? $qtyToDispense : $existStock->quantity;
                if ($qtyStockDispense>0 && $this->createStockCardDispense($existStock->stockId,$existStock->lotNo,$existStock->expiryDate,$qtyStockDispense)) {
                    $qtyToDispense = $qtyToDispense - $qtyStockDispense;
                }
                if ($qtyToDispense==0) break;
            }
        }

        //create for the rest
        if ($qtyToDispense>0) {
            $existStocks = \App\Models\Stock\StocksProducts::where('stockId',$this->stockId)->where('productCode',$this->productCode)->where('quantity','>',0)->whereNull('expiryDate')->orderBy('created_at')->get();
            foreach($existStocks as $existStock) {
                $qtyStockDispense = ($existStock->quantity>=$qtyToDispense) ? $qtyToDispense : $existStock->quantity;
                if ($qtyStockDispense>0 && $this->createStockCardDispense($existStock->stockId,$existStock->lotNo,$existStock->expiryDate,$qtyStockDispense)) {
                    $qtyToDispense = $qtyToDispense - $qtyStockDispense;
                }
                if ($qtyToDispense==0) break;
            }
        }

        //create unspecific card
        if ($qtyToDispense>0) {
            $this->createStockCardDispense($this->stockId,$this->lotNo,null,$qtyToDispense);
        }
    }

    private function createStockCardDispense($stockId=null,$lotNo=null,$expiryDate=null,$quantity=null) {
        try {
            $stockCard = new \App\Models\Stock\StocksCards();
            $stockCard->cardType = "dispensing";
            $stockCard->productCode = $this->productCode;
            $stockCard->stockFrom = ($stockId) ? $stockId : $this->stockId;
            $stockCard->lotNo = ($lotNo) ? $lotNo : $this->lotNo;
            $stockCard->expiryDate = $expiryDate;
            $stockCard->quantity = ($quantity) ? $quantity : $this->quantity;
            $stockCard->hn = ($this->prescription) ? $this->prescription->hn : null;
            $stockCard->encounterId = ($this->prescription) ? $this->prescription->encounterId : null;
            $stockCard->prescriptionsDispensingId = $this->id;
            $stockCard->save();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected $casts = [
      'statusLog' => 'array',
    ];
}
