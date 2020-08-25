<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterPatientsInsurancesAddNhsoRelated extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('patients_insurances', function (Blueprint $table) {
            $table->string('nhsoHCode')->nullable()->after('contractNo');
            $table->string('nhsoCAGCode')->nullable()->after('nhsoHCode');
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
            $table->dropColumn(['nhsoHCode','nhsoCAGCode']);
        });
    }
}
