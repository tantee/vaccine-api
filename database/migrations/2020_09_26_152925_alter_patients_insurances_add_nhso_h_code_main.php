<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterPatientsInsurancesAddNhsoHCodeMain extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('patients_insurances', function (Blueprint $table) {
            $table->string('nhsoHCodeMain')->nullable()->after('contractNo');
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
            $table->dropColumn(['nhsoHCodeMain']);
        });
    }
}
