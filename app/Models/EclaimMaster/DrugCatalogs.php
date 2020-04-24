<?php

namespace App\Models\EclaimMaster;

use Illuminate\Database\Eloquent\Model;

class DrugCatalogs extends Model
{
    protected $connection = 'eclaim';
    protected $table = 'l_drug_catalog';

    protected $primaryKey = 'HOSPDRUGCODE';
    public $incrementing = false;
    protected $keyType = 'string';
}
