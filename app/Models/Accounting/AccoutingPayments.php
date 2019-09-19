<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;
use App\Http\Controllers\Master\IdController;

class AccountingPayments extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    public function getAmountOutstandingAttribute() {
        return ($this->amount_due - $this->amount_paid >= 0) ? $this->amount_due - $this->amount_paid : 0;
    }

    public function toArray()
    {
        $toArray = parent::toArray();
        $toArray['amountOutstanding'] = $this->amount_outstanding;

        return $toArray;
    }

    public static function boot() {
        static::creating(function($model) {
            if (!isset($model->receiptId) || empty($model->receiptId)) {
                $model->receiptId = IdController::issueId('receipt',env('RECEIPT_ID_FORMAT', 'ym'),env('RECEIPT_ID_DIGIT', 6));
            }
        });

        parent::boot();
    }
}
