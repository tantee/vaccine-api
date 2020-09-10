<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class StocksDispensings extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    public function StocksRequest() {
        return $this->belongsTo('App\Models\Stock\StocksRequests','stocksRequestId','id');
    }

    public function Product() {
        return $this->hasOne('App\Models\Master\Products','productCode','productCode')->withTrashed();
    }

    public function StocksCards() {
        return $this->hasMany('App\Models\Stock\StocksCards','stocksDispensingId','id');
    }

    public static function boot() {
        static::saved(function($model) {
            $original = $model->getOriginal();
            if ($model->status == 'dispensed' && (!array_key_exists('status',$original) || $original["status"] != 'dispensed')) {
                $model->createStockCard();
            }
            if (array_key_exists('status',$original) && $original['status']=='dispensed' && $model->status!='dispensed') {
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
            $existStocks = \App\Models\Stock\StocksProducts::where('stockId',$this->stockFrom)->where('productCode',$this->productCode)->where('lotNo',$this->lotNo)->where('quantity','>',0)->get();
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
            $existStocks = \App\Models\Stock\StocksProducts::where('stockId',$this->stockFrom)->where('productCode',$this->productCode)->where('quantity','>',0)->whereNotNull('expiryDate')->orderBy('expiryDate')->get();
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
            $existStocks = \App\Models\Stock\StocksProducts::where('stockId',$this->stockFrom)->where('productCode',$this->productCode)->where('quantity','>',0)->whereNull('expiryDate')->orderBy('created_at')->get();
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
            $stockCard->stockFrom = ($stockId) ? $stockId : $this->stockFrom;
            $stockCard->stockTo = $this->stockTo;
            $stockCard->lotNo = ($lotNo) ? $lotNo : $this->lotNo;
            $stockCard->expiryDate = $expiryDate;
            $stockCard->quantity = ($quantity) ? $quantity : $this->quantity;
            $stockCard->stocksDispensingId = $this->id;
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
