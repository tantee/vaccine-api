<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterPrescriptionsDispensingsAddIsNotCharge extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('prescriptions_dispensings', function (Blueprint $table) {
            $table->dropColumn(['stockCardId']);
            $table->string('lotNo')->nullable()->after('stockId');
            $table->boolean('isNotCharge')->default(false)->after('transactionId');
            $table->renameColumn('amount', 'quantity');
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
            $table->dropColumn(['isNotCharge','lotNo']);
            $table->integer('stockCardId')->nullable()->after('stockId');
            $table->renameColumn('quantity', 'amount');
        });
    }
}
