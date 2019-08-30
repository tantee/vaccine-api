<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDoctors extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->string('doctorCode')->primary();
            $table->string('doctorType');
            $table->string('nameTH');
            $table->string('nameEN');
            $table->string('specialty');
            $table->string('mainHospital')->nullable();
            $table->string('licenseNo');
            $table->string('personId');
            $table->string('defaultTaxId')->nullable();
            $table->string('telephone');
            $table->string('email')->nullable();
            $table->json('photo')->nullable();
            $table->json('signature')->nullable();
            $table->text('note')->nullable();
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
        Schema::dropIfExists('doctors');
    }
}
