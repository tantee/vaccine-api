<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterProductsRemoveAccountCode extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['accountCodeStock','accountCodeCost','accountCodeIncome']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('accountCodeStock')->nullable()->after('price5');
            $table->string('accountCodeCost')->nullable()->after('accountCodeStock');
            $table->string('accountCodeIncome')->nullable()->after('accountCodeCost');
        });
    }
}
