<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('products', function(Blueprint $table)
		{
			$table->string('productCode', 50)->primary();
			$table->string('productName');
			$table->string('productNameEN');
			$table->string('productKeyword')->nullable();
			$table->string('eclaimCode')->nullable();
			$table->string('eclaimAdpType')->nullable();
			$table->string('cgdCode')->nullable();
			$table->string('category', 50);
			$table->string('categoryInsurance', 50);
			$table->string('categoryCgd', 50);
			$table->string('saleUnit')->nullable();
			$table->decimal('price1', 10);
			$table->decimal('price2', 10)->nullable();
			$table->decimal('price3', 10)->nullable();
			$table->decimal('price4', 10)->nullable();
			$table->decimal('price5', 10)->nullable();
			$table->enum('productType', ['medicine','supply','procedure','service','laboratory','radiology','package','doctorfee'])->nullable();
			$table->json('specification');
			$table->json('childProducts')->nullable();
			$table->json('itemizedProducts')->nullable();
			$table->boolean('isActive')->default(1)->index();
			$table->boolean('isHidden')->default(0)->index();
			$table->string('created_by')->nullable();
			$table->string('updated_by')->nullable();
			$table->string('deleted_by')->nullable();
			$table->softDeletes();
			$table->timestamps();
			$table->index(['productType','category']);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('products');
	}
}
