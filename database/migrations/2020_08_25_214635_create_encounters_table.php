<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEncountersTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('encounters', function(Blueprint $table)
		{
			$table->string('encounterId', 50)->primary();
			$table->string('encounterType', 10)->default('AMB');
			$table->string('hn');
			$table->string('clinicCode');
			$table->string('doctorCode');
			$table->string('locationCode');
			$table->string('locationSubunitCode')->nullable();
			$table->json('locationLog')->nullable();
			$table->string('currentLocation')->nullable();
			$table->string('nextLocation')->nullable();
			$table->dateTime('admitDateTime');
			$table->dateTime('dischargeDateTime')->nullable();
			$table->json('screening')->nullable();
			$table->json('summary')->nullable();
			$table->boolean('isTransactionLock')->nullable()->default(0);
			$table->string('status')->nullable()->default('checkedIn');
			$table->json('statusLog')->nullable();
			$table->integer('fromAppointmentId')->nullable();
			$table->string('created_by')->nullable();
			$table->string('updated_by')->nullable();
			$table->string('deleted_by')->nullable();
			$table->softDeletes();
			$table->timestamps();
			$table->index(['hn','encounterType']);
			$table->index(['encounterType','doctorCode']);
			$table->index(['encounterType','locationCode','clinicCode']);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('encounters');
	}
}
