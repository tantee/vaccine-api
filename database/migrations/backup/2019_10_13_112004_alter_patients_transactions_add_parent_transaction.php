<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterPatientsTransactionsAddParentTransaction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('patients_transactions', function (Blueprint $table) {
            $table->integer('parentTransactionId')->nullable()->after('id');
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
            $table->dropColumn('parentTransactionId');
        });
    }
}
