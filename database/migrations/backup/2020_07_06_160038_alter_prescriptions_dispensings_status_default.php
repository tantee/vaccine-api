<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterPrescriptionsDispensingsStatusDefault extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('prescriptions_dispensings', function (Blueprint $table) {
            $table->string('status')->default('prepared')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('prescriptions_dispensings', function (Blueprint $table) {
            $table->string('status')->change();
        });
    }
}
