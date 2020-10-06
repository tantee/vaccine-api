<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Models\Traits\UserStamps;
use Carbon\Carbon;

class PatientsMessages extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    public function scopeActive($query) {
      return $query->whereDate('beginDateTime','<=',Carbon::now())->where(function ($query) {
        $query->whereDate('endDateTime','>=',Carbon::now())->orWhereNull('endDateTime');
      });
    }

    public function scopeActiveAt($query,$date) {
      return $query->whereDate('beginDateTime','<=',$date)->where(function ($query) use ($date) {
        $query->whereDate('endDateTime','>=',$date)->orWhereNull('endDateTime');
      });
    }

    public function scopeActiveLocation($query,$locationCode = null) {
      $query = $query->whereDate('beginDateTime','<=',Carbon::now())
                ->where(function ($query) {
                  $query->whereDate('endDateTime','>=',Carbon::now())->orWhereNull('endDateTime');
                });
      if (!empty($locationCode)) {
        $query = $query->where(function ($query) use ($locationCode) {
                    $query->whereNull('locations')->orWhereJsonLength('locations',0)->orWhereJsonContains('locations',$locationCode);
                    if (Auth::guard('api')->check()) {
                      $query->orWhere('created_by',Auth::guard('api')->user()->username);
                    }
                 });
      }
      return $query;
    }

    protected $dates = [
        'beginDateTime',
        'endDateTime'
    ];

    protected $casts = [
      'locations' => 'array',
    ];
}
