<?php

namespace App\Models\Registration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class EncountersDispensings extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    public function Encounter() {
        return $this->hasOne('App\Models\Registration\Encounters','encounterId','encounterId')->without(['patient','Location','Clinic','Doctor','fromAppointment']);
    }

    public function Product() {
        return $this->hasOne('App\Models\Master\Products','productCode','productCode')->withTrashed();
    }

    public function StocksCards() {
        return $this->hasMany('App\Models\Stock\StocksCards','encountersDispensingId','id');
    }

    public function Transaction() {
        return $this->hasOne('App\Models\Patient\PatientsTransactions','id','transactionId');
    }

    public static function boot() {
        static::saved(function($model) {
            $original = $model->getOriginal();
            if ($model->status == 'dispensed' && (!array_key_exists('status',$original) || $original["status"] != 'dispensed')) {
                $model->createStockCard();
            }
            if (array_key_exists('status',$original) && $original['status']=='dispensed' && $model->status!='dispensed') {
                $model->StocksCards->each->delete();
            }
        });

        static::deleting(function($model) {
            if ($model->Transaction && !$model->Transaction->invoiceId) {
                $model->Transaction->delete();
                $model->transactionId = null;
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
            if ($this->stockId>10000) $existStocks = \App\Models\Stock\StocksProducts::where('stockId',$this->stockId)->where('encounterId',$this->encounterId)->where('productCode',$this->productCode)->where('lotNo',$this->lotNo)->where('quantity','>',0)->get();
            else $existStocks = \App\Models\Stock\StocksProducts::where('stockId',$this->stockId)->where('productCode',$this->productCode)->where('lotNo',$this->lotNo)->where('quantity','>',0)->get();
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
            if ($this->stockId>10000) $existStocks = \App\Models\Stock\StocksProducts::where('stockId',$this->stockId)->where('encounterId',$this->encounterId)->where('productCode',$this->productCode)->where('quantity','>',0)->whereNotNull('expiryDate')->orderBy('expiryDate')->get();
            else $existStocks = \App\Models\Stock\StocksProducts::where('stockId',$this->stockId)->where('productCode',$this->productCode)->where('quantity','>',0)->whereNotNull('expiryDate')->orderBy('expiryDate')->get();
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
            if ($this->stockId>10000) $existStocks = \App\Models\Stock\StocksProducts::where('stockId',$this->stockId)->where('encounterId',$this->encounterId)->where('productCode',$this->productCode)->where('quantity','>',0)->whereNull('expiryDate')->orderBy('created_at')->get();
            else $existStocks = \App\Models\Stock\StocksProducts::where('stockId',$this->stockId)->where('productCode',$this->productCode)->where('quantity','>',0)->whereNull('expiryDate')->orderBy('created_at')->get();
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
            $stockCard->cardDateTime = $this->updated_at;
            $stockCard->productCode = $this->productCode;
            $stockCard->stockFrom = ($stockId) ? $stockId : $this->stockId;
            $stockCard->lotNo = ($lotNo) ? $lotNo : $this->lotNo;
            $stockCard->expiryDate = $expiryDate;
            $stockCard->quantity = ($quantity) ? $quantity : $this->quantity;
            $stockCard->hn = ($this->encounter) ? $this->encounter->hn : null;
            $stockCard->encounterId = $this->encounterId;
            $stockCard->encountersDispensingId = $this->id;
            $stockCard->save();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function rebuildStocksCards () {
        if ($this->status == 'dispensed') {
            if ($this->StocksCards->count()==0 && $this->created_at > '2020-08-31') {
                $this->createStockCard();
            }
        }
    }

    protected $casts = [
      'statusLog' => 'array',
    ];
}
