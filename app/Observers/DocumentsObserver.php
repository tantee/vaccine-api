<?php

namespace App\Observers;

use App\Models\Document\Documents;

class DocumentsObserver
{
    /**
     * Handle the Documents "created" event.
     *
     * @param  \App\Models\Document\Documents  $documents
     * @return void
     */
    public function created(Documents $documents)
    {
        //
    }

    /**
     * Handle the Documents "updated" event.
     *
     * @param  \App\Models\Document\Documents  $documents
     * @return void
     */
    public function updated(Documents $documents)
    {
        //
    }

    public function saved(Documents $documents) {
        $original = $documents->getOriginal();

        if ($documents->templateCode=='cv19-vaccine-administration') {
            //Make duplication documents in same day has review status
            if ($documents->status=='approved') {
                \App\Models\Document\Documents::where('templateCode','cv19-vaccine-administration')
                ->where('hn',$documents->hn)
                ->whereDate('created_at',$documents->created_at)
                ->where('created_at','<',$documents->created_at)
                ->where('created_by',$documents->created_by)
                ->where('status','approved')
                ->update(['status'=>'retired']);
            }

            //Add to MOPH sending queue and Auto Discharge
            if ($documents->status=='approved') {
                \App\Jobs\Covid19\SendDataToMoph::dispatch($documents)->delay(now()->addMinutes(3));
                \App\Jobs\Covid19\AutoDischarge::dispatch($documents)->delay(now()->addMinutes(40));
            }
        }
    }

    /**
     * Handle the Documents "deleted" event.
     *
     * @param  \App\Models\Document\Documents  $documents
     * @return void
     */
    public function deleted(Documents $documents)
    {
        //
    }

    /**
     * Handle the Documents "restored" event.
     *
     * @param  \App\Models\Document\Documents  $documents
     * @return void
     */
    public function restored(Documents $documents)
    {
        //
    }

    /**
     * Handle the Documents "force deleted" event.
     *
     * @param  \App\Models\Document\Documents  $documents
     * @return void
     */
    public function forceDeleted(Documents $documents)
    {
        //
    }
}
