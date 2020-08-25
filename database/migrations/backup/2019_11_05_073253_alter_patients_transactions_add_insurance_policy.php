<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterPatientsTransactionsAddInsurancePolicy extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('patients_transactions', function (Blueprint $table) {
            $table->string('soldInsuranceCode',50)->nullable()->after('soldPatientsInsurancesId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('patients_transactions', function (Blueprint $table) {
            $table->dropColumn(['soldInsuranceCode']);
        });
    }
}
