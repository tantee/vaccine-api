<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterInsurancesServeAsPolicyOnly extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('insurances', function (Blueprint $table) {
            $table->dropColumn(['payerARCode','isChargeToPatient']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('insurances', function (Blueprint $table) {
            $table->string('payerARCode')->after('discount');
            $table->boolean('isChargeToPatient')->default(true)->after('isCoverageAll');
        });
    }
}
