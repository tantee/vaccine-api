<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePrescriptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('hn',20);
            $table->string('encounterId',50);
            $table->integer('documentId');
            $table->json('labels')->nullable();
            $table->json('dispensing')->nullable();
            $table->string('status');
            $table->json('statusLog')->nullable();
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
        Schema::dropIfExists('prescriptions');
    }
}
