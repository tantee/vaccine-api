<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('locations', function(Blueprint $table)
		{
			$table->string('locationCode', 50)->primary();
			$table->enum('locationType', ['opd','ward','facility']);
			$table->string('locationName');
			$table->string('locationTelephone');
			$table->string('locationDescription')->nullable();
			$table->json('subunits')->nullable();
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
		Schema::drop('locations');
	}
}
