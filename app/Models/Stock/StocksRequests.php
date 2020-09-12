<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class StocksRequests extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    public function Dispensings() {
        return $this->hasMany('App\Models\Stock\StocksDispensings','stocksRequestId','id');
    }

    public static function boot() {
        static::saved(function($model) {
            if ($model->status == 'completed') {
                //Auto dispense
                \App\Http\Controllers\Stock\StockController::dispenseStocksRequest($model->id);
            }

            if ($model->status == 'reject') {
                //Delete all dispense
                $model->Dispensings->each->delete();
            }
        });

        parent::boot();
    }

    protected $dates = [
        'requestDispensingDate',
    ];

    protected $casts = [
        'requestData' => 'array',
        'statusLog' => 'array',
    ];
}
