<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientsInsurancesTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('patients_insurances', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('hn', 20)->index();
			$table->string('payerType', 50);
			$table->string('payerCode', 50)->nullable();
			$table->boolean('isChargeToPatient')->default(1);
			$table->json('policies')->nullable();
			$table->json('clinics')->nullable();
			$table->integer('priority')->default(10);
			$table->date('beginDate');
			$table->date('endDate')->nullable();
			$table->decimal('limit', 10)->nullable();
			$table->decimal('limitToConfirm', 10)->nullable();
			$table->decimal('limitPerOpd', 10)->nullable();
			$table->decimal('limitPerIpd', 10)->nullable();
			$table->string('contractNo')->nullable();
			$table->string('nhsoHCode')->nullable();
			$table->string('nhsoCAGCode')->nullable();
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
		Schema::drop('patients_insurances');
	}
}
