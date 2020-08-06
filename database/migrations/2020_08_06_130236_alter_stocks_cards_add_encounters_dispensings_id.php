<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterStocksCardsAddEncountersDispensingsId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stocks_cards', function (Blueprint $table) {
            $table->integer('encountersDispensingsId')->nullable()->after('prescriptionsDispensingId');
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
            $table->dropColumn(['encountersDispensingsId']);
        });
    }
}
