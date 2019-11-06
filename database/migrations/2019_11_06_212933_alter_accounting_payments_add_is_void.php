<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAccountingPaymentsAddIsVoid extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounting_payments', function (Blueprint $table) {
            $table->boolean('isVoid')->default(false)->after('note');
            $table->datetime('isVoidDateTime')->nullable()->after('isVoid');
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
            $table->dropColumn(['isVoid','isVoidDateTime']);
        });
    }
}
