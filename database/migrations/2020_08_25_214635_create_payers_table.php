<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayersTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('payers', function(Blueprint $table)
		{
			$table->string('payerCode', 50)->primary();
			$table->string('payerName');
			$table->string('payerAddress')->nullable();
			$table->string('payerTelephoneNo')->nullable();
			$table->string('payerTaxNo')->nullable();
			$table->integer('creditPeriod')->nullable();
			$table->json('overridePrices')->nullable();
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
		Schema::drop('payers');
	}
}
