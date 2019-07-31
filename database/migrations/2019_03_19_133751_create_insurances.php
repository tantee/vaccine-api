<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInsurances extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('insurances', function (Blueprint $table) {
            $table->string('insuranceCode')->primary();
            $table->string('insuranceName');
            $table->enum('priceLevel',['1','2','3','4','5'])->default('1');
            $table->decimal('discount',5,2)->nullable();
            $table->boolean('isCoverageAll')->default(true);
            $table->boolean('isChargeToPatient')->default(true);
            $table->boolean('isApplyToOpd')->default(true);
            $table->boolean('isApplyToIpd')->default(true);
            $table->json('conditions')->nullable();
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
        Schema::dropIfExists('insurances');
    }
}
