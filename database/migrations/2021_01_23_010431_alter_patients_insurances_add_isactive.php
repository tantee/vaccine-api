<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterPatientsInsurancesAddIsactive extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('patients_insurances', function (Blueprint $table) {
            $table->boolean('isTechnicalActive')->default(1)->index()->after('nhsoCAGCode');
            $table->boolean('isActive')->default(1)->index()->after('isTechnicalActive');
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
            $table->dropColumn(['isTechnicalActive','isActive']);
        });
    }
}
