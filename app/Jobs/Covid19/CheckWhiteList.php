<?php

namespace App\Jobs\Covid19;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Moph\Whitelists;
use App\Http\Controllers\Export\MOPHExportController;

class CheckWhiteList implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $whitelist;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Whitelists $whitelist)
    {
        $this->whitelist = $whitelist;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        MOPHExportController::checkWhitelistSingle($this->whitelist);
    }
}
