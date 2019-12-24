<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterPatientsTransactionsAddOverridePrice extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('patients_transactions', function (Blueprint $table) {
            $table->decimal('overridePrice',10,2)->nullable()->after('quantity');
            $table->decimal('overrideDiscount',5,2)->nullable()->after('overridePrice');
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
            $table->dropColumn(['overridePrice','overrideDiscount']);
        });
    }
}
