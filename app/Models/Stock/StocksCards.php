<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class StocksCards extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    public static function boot() {
        static::created(function($model) {
            $stockFrom = \App\Models\Stock\StocksProducts::firstOrCreate(
                ['productCode'=>$model->productCode,'stockId'=>$model->stockFrom,'lotNo'=>$model->lotNo],
                ['productCode'=>$model->productCode,'stockId'=>$model->stockFrom,'lotNo'=>$model->lotNo,'expiryDate'=>$model->expiryDate]
            );
            $stockFrom->amount = $stockFrom->amount - $model->amount;
            $stockFrom->save();

            if (isset($model->stockTo) && !empty($model->stockTo)) {
                $stockTo = \App\Models\Stock\StocksProducts::firstOrCreate(
                    ['productCode'=>$model->productCode,'stockId'=>$model->stockTo,'lotNo'=>$model->lotNo],
                    ['productCode'=>$model->productCode,'stockId'=>$model->stockTo,'lotNo'=>$model->lotNo,'expiryDate'=>$model->expiryDate]
                );
                $stockTo->amount = $stockTo->amount + $model->amount;
                $stockTo->save();
            }
        });

        static::updated(function($model){
            $original = $model->getOriginal();

            //rollback original
            $stockFrom = \App\Models\Stock\StocksProducts::firstOrCreate(['productCode'=>$original['productCode'],'stockId'=>$original['stockFrom'],'lotNo'=>$original['lotNo']],['expiryDate'=>$original['expiryDate']]);
            $stockFrom->amount = $stockFrom->amount + $original['amount'];
            $stockFrom->save();

            if (isset($original['stockTo']) && !empty($original['stockTo'])) {
                $stockTo = \App\Models\Stock\StocksProducts::firstOrCreate(['productCode'=>$original['productCode'],'stockId'=>$original['stockTo'],'lotNo'=>$original['lotNo']],['expiryDate'=>$original['expiryDate']]);
                $stockTo->amount = $stockTo->amount - $original['amount'];
                $stockTo->save();
            }

            //update new movement
            $stockFrom = \App\Models\Stock\StocksProducts::firstOrCreate(['productCode'=>$model->productCode,'stockId'=>$model->stockFrom,'lotNo'=>$model->lotNo],['expiryDate'=>$model->expiryDate]);
            $stockFrom->amount = $stockFrom->amount - $model->amount;
            $stockFrom->save();

            if (isset($model->stockTo) && !empty($model->stockTo)) {
                $stockTo = \App\Models\Stock\StocksProducts::firstOrCreate(['productCode'=>$model->productCode,'stockId'=>$model->stockTo,'lotNo'=>$model->lotNo],['expiryDate'=>$model->expiryDate]);
                $stockTo->amount = $stockTo->amount + $model->amount;
                $stockTo->save();
            }
        });

        static::deleted(function($model){
            $stockFrom = \App\Models\Stock\StocksProducts::firstOrCreate(['productCode'=>$model->productCode,'stockId'=>$model->stockFrom,'lotNo'=>$model->lotNo],['expiryDate'=>$model->expiryDate]);
            $stockFrom->amount = $stockFrom->amount + $model->amount;
            $stockFrom->save();

            if (isset($model->stockTo) && !empty($model->stockTo)) {
                $stockTo = \App\Models\Stock\StocksProducts::firstOrCreate(['productCode'=>$model->productCode,'stockId'=>$model->stockTo,'lotNo'=>$model->lotNo],['expiryDate'=>$model->expiryDate]);
                $stockTo->amount = $stockTo->amount - $model->amount;
                $stockTo->save();
            }
        });

        static::restored(function($model){
            $stockFrom = \App\Models\Stock\StocksProducts::firstOrCreate(['productCode'=>$model->productCode,'stockId'=>$model->stockFrom,'lotNo'=>$model->lotNo],['expiryDate'=>$model->expiryDate]);
            $stockFrom->amount = $stockFrom->amount - $model->amount;
            $stockFrom->save();

            if (isset($model->stockTo) && !empty($model->stockTo)) {
                $stockTo = \App\Models\Stock\StocksProducts::firstOrCreate(['productCode'=>$model->productCode,'stockId'=>$model->stockTo,'lotNo'=>$model->lotNo],['expiryDate'=>$model->expiryDate]);
                $stockTo->amount = $stockTo->amount + $model->amount;
                $stockTo->save();
            }
        });

        parent::boot();
    }
}
