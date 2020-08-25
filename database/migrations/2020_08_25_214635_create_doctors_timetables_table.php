<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDoctorsTimetablesTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('doctors_timetables', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('doctorCode', 20)->index();
			$table->string('clinicCode')->index();
			$table->string('dayOfweek');
			$table->time('beginTime');
			$table->time('endTime');
			$table->integer('limitTotalCase')->nullable();
			$table->integer('limitNewCase')->nullable();
			$table->decimal('dfHospitalPercent', 5);
			$table->decimal('dfMinimum')->nullable();
			$table->decimal('dfMaximum')->nullable();
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
		Schema::drop('doctors_timetables');
	}
}
