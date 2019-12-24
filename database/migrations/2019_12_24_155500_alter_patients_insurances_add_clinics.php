<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterPatientsInsurancesAddClinics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('patients_insurances', function (Blueprint $table) {
            $table->json('clinics')->nullable()->after('policies');
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
            $table->dropColumn(['clinics']);
        });
    }
}
