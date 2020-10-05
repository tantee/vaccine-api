<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductsPromotionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products_promotions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('productCode', 50);
            $table->datetime('beginDateTime');
            $table->datetime('endDateTime')->nullable();
            $table->decimal('price1', 10);
            $table->decimal('price2', 10)->nullable();
            $table->decimal('price3', 10)->nullable();
            $table->decimal('price4', 10)->nullable();
            $table->decimal('price5', 10)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products_promotions');
    }
}
