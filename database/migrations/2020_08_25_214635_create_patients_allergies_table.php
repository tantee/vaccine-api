<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientsAllergiesTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('patients_allergies', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('hn', 20)->index();
			$table->string('allergyType');
			$table->string('suspectedProduct');
			$table->string('suspectedGPU')->nullable();
			$table->string('probability');
			$table->string('severity');
			$table->text('manifestation')->nullable();
			$table->string('informationSource');
			$table->boolean('isNewOccurence')->default(0);
			$table->date('isNewOccurenceDate')->nullable();
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
		Schema::drop('patients_allergies');
	}
}
