<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashiersPeriodsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('cashiers_periods', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('cashierId', 20)->index();
			$table->dateTime('startDateTime');
			$table->dateTime('endDateTime')->nullable();
			$table->decimal('initialCash', 10);
			$table->decimal('finalCash', 10)->nullable();
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
		Schema::drop('cashiers_periods');
	}
}
