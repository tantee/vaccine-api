<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterStocksProductsStockIdType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stocks_products', function (Blueprint $table) {
            $table->interger('stockId')->change();
            $table->string('encounterId', 50)->nullable()->after('stockId');
            $table->index(['stockId','encounterId','productCode']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stocks_products', function (Blueprint $table) {
            $table->string('stockId')->change();
            $table->dropColumn(['encounterId']);
            $table->dropIndex(['stockId','encounterId','productCode']);
        });
    }
}
