<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterStocksCardsAddCardDateTime extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stocks_cards', function (Blueprint $table) {
            $table->timestamp('cardDateTime')->useCurrent()->after('id');
            $table->integer('stockFrom')->nullable()->change();
            $table->integer('documentId')->nullable()->after('prescriptionId');
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
            $table->dropColumn(['cardDateTime','documentId']);
            $table->integer('stockFrom')->change();
        });
    }
}
