<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePrescriptionsDispensings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prescriptions_dispensings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('prescriptionId');
            $table->string('productCode');
            $table->integer('amount');
            $table->integer('stockId')->nullable();
            $table->integer('stockCardId')->nullable();
            $table->integer('transactionId')->nullable();
            $table->string('status');
            $table->json('statusLog')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->SoftDeletes();
            $table->timestamps();
            $table->index(['prescriptionId']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prescriptions_dispensings');
    }
}
