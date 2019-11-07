<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterPatientsTransactionsAddIsRevised extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('patients_transactions', function (Blueprint $table) {
            $table->boolean('isRevised')->default(false)->after('isChargable');
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
            $table->dropColumn(['isRevised']);
        });
    }
}
