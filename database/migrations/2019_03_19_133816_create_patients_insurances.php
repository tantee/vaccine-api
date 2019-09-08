<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePatientsInsurances extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patients_insurances', function (Blueprint $table) {
            $table->increments('id');
            $table->string('hn');
            $table->string('insuranceCode');
            $table->integer('priority')->nullable()->default('10');
            $table->date('beginDate');
            $table->date('endDate')->nullable();
            $table->decimal('limit',10,2)->nullable();
            $table->decimal('limitToConfirm',10,2)->nullable();
            $table->decimal('limitPerOpd',10,2)->nullable();
            $table->decimal('limitPerIpd',10,2)->nullable();
            $table->string('contractNo')->nullable();
            $table->string('contractPayer1')->nullable();
            $table->string('contractPayer2')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->SoftDeletes();
            $table->timestamps();
            $table->index(['hn']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patients_insurances');
    }
}
