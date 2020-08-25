<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterStocksProductsRenameAmount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stocks_products', function (Blueprint $table) {
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
        Schema::table('stocks_products', function (Blueprint $table) {
            $table->renameColumn('quantity', 'amount');
        });
    }
}
