<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDoctorsTimetablesFlagsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('doctors_timetables_flags', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('flagText');
			$table->date('flagDate');
			$table->time('flagBeginTime')->nullable();
			$table->time('flagEndTime')->nullable();
			$table->string('doctorCode')->nullable();
			$table->string('clinicCode')->nullable();
			$table->boolean('isClinicClose');
			$table->boolean('isUnappointable');
			$table->json('overrideParameters')->nullable();
			$table->string('note')->nullable();
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
		Schema::drop('doctors_timetables_flags');
	}
}
