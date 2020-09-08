<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterStocksCardsAddStocksDispensingId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stocks_cards', function (Blueprint $table) {
            $table->integer('stocksDispensingId')->nullable()->after('encountersDispensingId');
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
            $table->dropColumn(['stocksDispensingId']);
        });
    }
}
