<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterStocksCardsRenameAmount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stocks_cards', function (Blueprint $table) {
            $table->renameColumn('amount', 'quantity');
            $table->renameColumn('prescriptionId', 'prescriptionsDispensingId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stocks_cards', function (Blueprint $table) {
            $table->renameColumn('quantity', 'amount');
            $table->renameColumn('prescriptionsDispensingId', 'prescriptionId');
        });
    }
}
