<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterPatientsInsurancesMultiplePolicies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('patients_insurances', function (Blueprint $table) {
            $table->dropColumn(['insuranceCode','contractPayer1','contractPayer2']);
            $table->string('payerType',50)->after('hn');
            $table->string('payerCode',50)->nullable()->after('payerType');
            $table->json('policies',50)->nullable()->after('payerCode');
            $table->boolean('isChargeToPatient')->default(false)->after('payerCode');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('patients_insurances', function (Blueprint $table) {
            $table->dropColumn(['payerType','payerCode','isChargeToPatient','policies']);
            $table->string('insuranceCode',50)->after('hn');
            $table->string('contractPayer1')->nullable()->after('contractNo');
            $table->string('contractPayer2')->nullable()->after('contractPayer1');
        });
    }
}
