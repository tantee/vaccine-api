<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class StocksCards extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    public function Product() {
        return $this->hasOne('App\Models\Master\Products','productCode','productCode')->withTrashed();
    }

    public static function boot() {
        static::created(function($model) {
            if (isset($model->stockFrom) && !empty($model->stockFrom)) {
                $stockQuery = ['productCode'=>$model->productCode,'stockId'=>$model->stockFrom,'lotNo'=>$model->lotNo];
                if ($model->stockForm>10000) $stockQuery['encounterId'] = $model->encounterId;
                $stockFrom = \App\Models\Stock\StocksProducts::firstOrCreate($stockQuery,['expiryDate'=>$model->expiryDate]);
                $stockFrom->quantity = $stockFrom->quantity - $model->quantity;
                $stockFrom->save();
            }
            
            if (isset($model->stockTo) && !empty($model->stockTo)) {
                $stockQuery = ['productCode'=>$model->productCode,'stockId'=>$model->stockTo,'lotNo'=>$model->lotNo];
                if ($model->stockTo>10000) $stockQuery['encounterId'] = $model->encounterId;
                $stockTo = \App\Models\Stock\StocksProducts::firstOrCreate($stockQuery,['expiryDate'=>$model->expiryDate]);
                $stockTo->quantity = $stockTo->quantity + $model->quantity;
                $stockTo->save();
            }
        });

        static::updated(function($model){
            $original = $model->getOriginal();

            //rollback original
            if (isset($original['stockFrom']) && !empty($original['stockFrom'])) {
                $stockQuery = ['productCode'=>$original['productCode'],'stockId'=>$original['stockFrom'],'lotNo'=>$original['lotNo']];
                if ($original['stockFrom']>10000) $stockQuery['encounterId'] = $original['encounterId'];
                $stockFrom = \App\Models\Stock\StocksProducts::firstOrCreate($stockQuery,['expiryDate'=>$original['expiryDate']]);
                $stockFrom->quantity = $stockFrom->quantity + $original['quantity'];
                $stockFrom->save();
            }

            if (isset($original['stockTo']) && !empty($original['stockTo'])) {
                $stockQuery = ['productCode'=>$original['productCode'],'stockId'=>$original['stockTo'],'lotNo'=>$original['lotNo']];
                if ($original['stockTo']>10000) $stockQuery['encounterId'] = $original['encounterId'];
                $stockTo = \App\Models\Stock\StocksProducts::firstOrCreate($stockQuery,['expiryDate'=>$original['expiryDate']]);
                $stockTo->quantity = $stockTo->quantity - $original['quantity'];
                $stockTo->save();
            }

            //update new movement
            if (isset($model->stockFrom) && !empty($model->stockFrom)) {
                $stockQuery = ['productCode'=>$model->productCode,'stockId'=>$model->stockFrom,'lotNo'=>$model->lotNo];
                if ($model->stockForm>10000) $stockQuery['encounterId'] = $model->encounterId;
                $stockFrom = \App\Models\Stock\StocksProducts::firstOrCreate($stockQuery,['expiryDate'=>$model->expiryDate]);
                $stockFrom->quantity = $stockFrom->quantity - $model->quantity;
                $stockFrom->save();
            }

            if (isset($model->stockTo) && !empty($model->stockTo)) {
                $stockQuery = ['productCode'=>$model->productCode,'stockId'=>$model->stockTo,'lotNo'=>$model->lotNo];
                if ($model->stockTo>10000) $stockQuery['encounterId'] = $model->encounterId;
                $stockTo = \App\Models\Stock\StocksProducts::firstOrCreate($stockQuery,['expiryDate'=>$model->expiryDate]);
                $stockTo->quantity = $stockTo->quantity + $model->quantity;
                $stockTo->save();
            }
        });

        static::deleted(function($model){
            if (isset($model->stockFrom) && !empty($model->stockFrom)) {
                $stockQuery = ['productCode'=>$model->productCode,'stockId'=>$model->stockFrom,'lotNo'=>$model->lotNo];
                if ($model->stockForm>10000) $stockQuery['encounterId'] = $model->encounterId;
                $stockFrom = \App\Models\Stock\StocksProducts::firstOrCreate(['productCode'=>$model->productCode,'stockId'=>$model->stockFrom,'lotNo'=>$model->lotNo],['expiryDate'=>$model->expiryDate]);
                $stockFrom->quantity = $stockFrom->quantity + $model->quantity;
                $stockFrom->save();
            }

            if (isset($model->stockTo) && !empty($model->stockTo)) {
                $stockQuery = ['productCode'=>$model->productCode,'stockId'=>$model->stockTo,'lotNo'=>$model->lotNo];
                if ($model->stockTo>10000) $stockQuery['encounterId'] = $model->encounterId;
                $stockTo = \App\Models\Stock\StocksProducts::firstOrCreate(['productCode'=>$model->productCode,'stockId'=>$model->stockTo,'lotNo'=>$model->lotNo],['expiryDate'=>$model->expiryDate]);
                $stockTo->quantity = $stockTo->quantity - $model->quantity;
                $stockTo->save();
            }
        });

        static::restored(function($model){
            if (isset($model->stockFrom) && !empty($model->stockFrom)) {
                $stockQuery = ['productCode'=>$model->productCode,'stockId'=>$model->stockFrom,'lotNo'=>$model->lotNo];
                if ($model->stockForm>10000) $stockQuery['encounterId'] = $model->encounterId;
                $stockFrom = \App\Models\Stock\StocksProducts::firstOrCreate(['productCode'=>$model->productCode,'stockId'=>$model->stockFrom,'lotNo'=>$model->lotNo],['expiryDate'=>$model->expiryDate]);
                $stockFrom->quantity = $stockFrom->quantity - $model->quantity;
                $stockFrom->save();
            }

            if (isset($model->stockTo) && !empty($model->stockTo)) {
                $stockQuery = ['productCode'=>$model->productCode,'stockId'=>$model->stockTo,'lotNo'=>$model->lotNo];
                if ($model->stockTo>10000) $stockQuery['encounterId'] = $model->encounterId;
                $stockTo = \App\Models\Stock\StocksProducts::firstOrCreate(['productCode'=>$model->productCode,'stockId'=>$model->stockTo,'lotNo'=>$model->lotNo],['expiryDate'=>$model->expiryDate]);
                $stockTo->quantity = $stockTo->quantity + $model->quantity;
                $stockTo->save();
            }
        });

        parent::boot();
    }

    protected $dates = [
        'expiryDate',
    ];

}
