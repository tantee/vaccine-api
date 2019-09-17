<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class AccountingInvoices extends Model
{
    use SoftDeletes,UserStamps;

    protected $primaryKey = 'invoiceId';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public function Transactions() {
        return $this->hasMany('App\Models\Patient\PatientsTransactions','invoiceId','invoiceId');
    }

    public function Payments() {
        return $this->hasMany('App\Models\Accounting\AccountingPayments','invoiceId','invoiceId');
    }

    public function Document() {
        return $this->hasOne('App\Models\Document\Documents','id','documentId');
    }

    public static function boot() {
        static::creating(function($model) {
            if (!isset($model->invoiceId) || empty($model->invoiceId)) {
                $model->invoiceId = IdController::issueId('invoice',env('INVOICE_ID_FORMAT', '\I\N\Vym'),env('INVOICE_ID_DIGIT', 6));
            }
        });

        parent::boot();
    }
}
