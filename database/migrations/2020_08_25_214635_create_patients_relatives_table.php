<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientsRelativesTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('patients_relatives', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('hn', 20)->index();
			$table->string('name');
			$table->string('relation')->nullable();
			$table->string('mobileNo')->nullable();
			$table->string('telephoneNo')->nullable();
			$table->string('email')->nullable();
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
		Schema::drop('patients_relatives');
	}
}
