<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->string('productCode',50)->primary();
            $table->string('productName');
            $table->string('productNameEN');
            $table->string('productKeyword')->nullable();
            $table->string('category');
            $table->string('categoryInsurance');
            $table->string('categoryCgd');
            $table->string('saleUnit')->nullable();
            $table->decimal('price1',10,2);
            $table->decimal('price2',10,2)->nullable();
            $table->decimal('price3',10,2)->nullable();
            $table->decimal('price4',10,2)->nullable();
            $table->decimal('price5',10,2)->nullable();
            $table->string('cgdCode')->nullable();
            $table->string('cgdAccount')->nullable(); //บัญชียา ED, NED
            $table->decimal('cgdPrice',10,2)->nullable();
            $table->string('accountCodeStock')->nullable();
            $table->string('accountCodeCost')->nullable();
            $table->string('accountCodeIncome');
            $table->enum('productType',['medicine','supply','procedure','service','laboratory','radiology']);
            $table->json('specification');
            $table->boolean('isActive')->default(true);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->SoftDeletes();
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
        Schema::dropIfExists('products');
    }
}
