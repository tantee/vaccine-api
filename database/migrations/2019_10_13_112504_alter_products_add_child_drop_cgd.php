<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterProductsAddChildDropCgd extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['cgdCode','cgdAccount','cgdPrice']);
            $table->json('childProducts')->nullable()->default(new Expression('(JSON_ARRAY())'))->after('specification');
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
            $table->dropColumn(['childProducts']);
            $table->string('cgdCode')->nullable()->after('price5');
            $table->string('cgdAccount')->nullable()->after('cgdCode');
            $table->decimal('cgdPrice',10,2)->nullable()->after('cgdAccount');
        });
    }
}
