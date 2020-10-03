<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterPatientsTransactionsAddExcess extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('patients_transactions', function (Blueprint $table) {
            $table->decimal('soldCoverPrice', 10)->nullable()->after('soldFinalPrice');
            $table->decimal('soldFinalCoverPrice', 10)->nullable()->after('soldCoverPrice');
            $table->decimal('soldFinalExcessPrice', 10)->nullable()->after('soldFinalCoverPrice');
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
            $table->dropColumn(['soldFinalExcessPrice','soldFinalCoverPrice','soldCoverPrice']);
        });
    }
}
