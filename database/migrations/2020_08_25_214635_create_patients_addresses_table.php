<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientsAddressesTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('patients_addresses', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('hn', 20);
			$table->string('addressType', 20)->default('contact');
			$table->string('address');
			$table->string('village')->nullable();
			$table->string('moo')->nullable();
			$table->string('trok')->nullable();
			$table->string('soi')->nullable();
			$table->string('street')->nullable();
			$table->string('subdistrict')->nullable();
			$table->string('district')->nullable();
			$table->string('province')->nullable();
			$table->string('country');
			$table->string('postCode')->nullable();
			$table->string('created_by')->nullable();
			$table->string('updated_by')->nullable();
			$table->string('deleted_by')->nullable();
			$table->softDeletes();
			$table->timestamps();
			$table->index(['hn','addressType']);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('patients_addresses');
	}
}
