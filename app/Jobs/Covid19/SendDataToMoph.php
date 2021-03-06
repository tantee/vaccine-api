<?php

namespace App\Jobs\Covid19;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Document\Documents;
use App\Http\Controllers\Export\MOPHExportController;

class SendDataToMoph implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $document;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Documents $document)
    {
        $this->document = $document;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->document->status=='approved' && $this->document->templateCode=='cv19-vaccine-administration') {
            MOPHExportController::sendSingleData($this->document);
        }
    }

    public $tries = 3;
}
