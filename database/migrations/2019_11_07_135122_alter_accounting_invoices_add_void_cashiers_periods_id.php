<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAccountingInvoicesAddVoidCashiersPeriodsId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounting_invoices', function (Blueprint $table) {
            $table->integer('cashiersPeriodsId')->nullable()->after('id');
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
        Schema::table('accounting_invoices', function (Blueprint $table) {
            $table->dropColumn(['isVoidCashiersPeriodsId','cashiersPeriodsId']);
        });
    }
}
