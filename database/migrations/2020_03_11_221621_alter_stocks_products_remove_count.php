<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterStocksProductsRemoveCount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stocks_products', function (Blueprint $table) {
            $table->dropColumn(['countDateTime']);
            $table->integer('amount')->default(0)->change();
            $table->string('lotNo')->nullable()->after('productCode');
            $table->datetime('expiryDate')->nullable()->after('lotNo');
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
            $table->datetime('countDateTime')->after('amount');
            $table->integer('amount')->change();
            $table->dropColumn(['countDateTime','lotNo','expiryDate']);
        });
    }
}
