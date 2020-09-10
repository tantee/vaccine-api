<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStocksDispensingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stocks_dispensings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('stocksRequestId')->index()->nullable();
            $table->string('productCode');
            $table->integer('quantity');
            $table->integer('stockTo')->nullable();
            $table->integer('stockFrom')->nullable();
            $table->string('lotNo')->nullable();
            $table->string('status')->default('prepared');
            $table->json('statusLog')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->softDeletes();
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
        Schema::dropIfExists('stocks_dispensings');
    }
}
