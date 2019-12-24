<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRadiology extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('radiology', function (Blueprint $table) {
            $table->increments('id');
            $table->string('hn');
            $table->string('accessionNumber')->nullable();
            $table->string('modality');
            $table->datetime('studyDateTime');
            $table->datetime('requestDateTime')->nullable();
            $table->datetime('reportDateTime')->nullable();
            $table->string('uid');
            $table->string('referringDoctor')->nullable();
            $table->string('reportingDoctor')->nullable();
            $table->interger('imageCount')->default(0);
            $table->string('requestDocumentId')->nullable();
            $table->string('reportDocumentId')->nullable();
            $table->string('status')->default('COMPLETED');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->SoftDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('radiology');
    }
}
