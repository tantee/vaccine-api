<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VaccineStatistics extends Mailable implements ShouldQueue
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
        $statistics = \App\Http\Controllers\Covid19\VaccineController::getStatistics($this->beginDate,$this->endDate);
        $subject = "รายงานผู้รับวัคซีน วันที่ ".\Carbon\Carbon::parse($this->beginDate)->timezone(config('app.timezone'))->format('Y-m-d');
        if (!empty($this->endDate) && $this->beginDate!=$this->endDate) $subject = $subject." ถึงวันที่ ".\Carbon\Carbon::parse($this->endDate)->timezone(config('app.timezone'))->format('Y-m-d');

        return $this->view('emails.vaccinestatistics')
                    ->subject($subject)
                    ->with($statistics);
    }
}
