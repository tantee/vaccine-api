<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrescriptionsDispensingsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('prescriptions_dispensings', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('prescriptionId')->index();
			$table->string('productCode');
			$table->integer('quantity');
			$table->integer('stockId')->nullable();
			$table->string('lotNo')->nullable();
			$table->integer('transactionId')->nullable();
			$table->boolean('isNotCharge')->default(0);
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
		Schema::drop('prescriptions_dispensings');
	}
}
