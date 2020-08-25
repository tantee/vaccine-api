<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAccountingPaymentsAddVoidCashiersPeriodsId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounting_payments', function (Blueprint $table) {
            $table->integer('isVoidCashiersPeriodsId')->nullable()->after('isVoidDateTime');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accounting_payments', function (Blueprint $table) {
            $table->dropColumn(['isVoidCashiersPeriodsId']);
        });
    }
}
