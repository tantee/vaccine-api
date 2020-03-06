<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterRadiologiesDoctorToDoctorCode extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('radiologies', function (Blueprint $table) {
            $table->renameColumn('reportingDoctor', 'reportingDoctorCode');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('radiologies', function (Blueprint $table) {
            $table->renameColumn('reportingDoctorCode', 'reportingDoctor');
        });
    }
}
