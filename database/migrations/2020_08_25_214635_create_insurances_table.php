<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInsurancesTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('insurances', function(Blueprint $table)
		{
			$table->string('insuranceCode', 50)->primary();
			$table->string('insuranceName');
			$table->enum('priceLevel', ['1','2','3','4','5'])->default('1');
			$table->decimal('discount', 5)->nullable();
			$table->boolean('isCoverageAll')->default(1);
			$table->boolean('isApplyToOpd')->default(1);
			$table->boolean('isApplyToIpd')->default(1);
			$table->json('conditions')->nullable();
			$table->text('note')->nullable();
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
		Schema::drop('insurances');
	}
}
