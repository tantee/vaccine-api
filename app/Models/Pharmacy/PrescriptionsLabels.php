<?php

namespace App\Models\Pharmacy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class PrescriptionsLabels extends Model
{
    use SoftDeletes,UserStamps;

    public function Prescription() {
        return $this->belongsTo('App\Models\Pharmacy\Prescriptions','prescriptionId','id');
    }

    protected $casts = [
      'directions' => 'array',
      'cautions' => 'array',
    ];
}
