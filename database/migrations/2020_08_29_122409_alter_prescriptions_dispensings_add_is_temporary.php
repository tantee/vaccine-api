<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterPrescriptionsDispensingsAddIsTemporary extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('prescriptions_dispensings', function (Blueprint $table) {
            $table->boolean('isTemporary')->default(0)->after('isNotCharge');
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
            $table->dropColumn(['isTemporary']);
        });
    }
}
