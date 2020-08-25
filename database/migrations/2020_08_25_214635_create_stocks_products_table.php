<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStocksProductsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('stocks_products', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('stockId');
			$table->string('productCode');
			$table->string('lotNo')->nullable();
			$table->dateTime('expiryDate')->nullable();
			$table->decimal('unitCost', 10)->nullable();
			$table->integer('quantity')->default(0);
			$table->string('created_by')->nullable();
			$table->string('updated_by')->nullable();
			$table->string('deleted_by')->nullable();
			$table->softDeletes();
			$table->timestamps();
			$table->index(['stockId','productCode']);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('stocks_products');
	}
}
