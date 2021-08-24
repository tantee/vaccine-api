<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VaccineAppointments extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    private $beginDate;
    private $endDate;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($beginDate=null,$endDate=null)
    {
        if ($beginDate) $beginDate = \Carbon\Carbon::parse($beginDate)->timezone(config('app.timezone'));
        else $beginDate = \Carbon\Carbon::now()->subDays(1);

        if ($endDate) $endDate = \Carbon\Carbon::parse($endDate)->timezone(config('app.timezone'));
        else $endDate = $beginDate;

        $this->beginDate = $beginDate;
        $this->endDate = $endDate;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $appointments = \App\Http\Controllers\Covid19\VaccineController::getAppointments($this->beginDate,$this->endDate);
        $subject = "รายการสรุปยอดนัดหมายล่วงหน้า ณ วันที่ ".\Carbon\Carbon::now()->format('Y-m-d');

        return $this->view('emails.vaccineappointments')
                ->subject($subject)
                ->with([
                    "appointments" => $appointments,
                    "beginDate" => $this->beginDate->format('Y-m-d'),
                    "endDate" => $this->endDate->format('Y-m-d'),
                    "reportDate" => \Carbon\Carbon::now()->format('Y-m-d'),
                    "hospital_code" => (!empty(env('FIELD_HOSPITAL_CODE',''))) ? env('FIELD_HOSPITAL_CODE','') : env('HOSPITAL_CODE', ''),
                    "hospital_name" => (!empty(env('FIELD_HOSPITAL_NAME',''))) ? env('FIELD_HOSPITAL_NAME','') : env('HOSPITAL_NAME', ''),
                ]);
    }
}
