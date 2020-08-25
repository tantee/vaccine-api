<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('patients', function(Blueprint $table)
		{
			$table->string('hn', 20)->primary();
			$table->date('dateOfBirth');
			$table->date('dateOfDeath')->nullable();
			$table->tinyInteger('personIdType');
			$table->string('personId');
			$table->boolean('personIdVerified')->default(0);
			$table->json('personIdDetail')->nullable();
			$table->string('religion')->nullable();
			$table->string('nationality')->nullable();
			$table->string('race')->nullable();
			$table->tinyInteger('sex');
			$table->string('maritalStatus')->nullable();
			$table->string('occupation')->nullable();
			$table->string('primaryMobileNo');
			$table->string('primaryTelephoneNo')->nullable();
			$table->string('primaryEmail')->nullable();
			$table->string('maternalName')->nullable();
			$table->string('paternalName')->nullable();
			$table->string('spouseName')->nullable();
			$table->enum('classifiedLevel', ['normal','vip','vvip'])->default('normal');
			$table->enum('status', ['active','inactive','merged'])->default('active');
			$table->string('mergedTo')->nullable();
			$table->string('created_by')->nullable();
			$table->string('updated_by')->nullable();
			$table->string('deleted_by')->nullable();
			$table->softDeletes();
			$table->timestamps();
			$table->index(['personIdType','personId']);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('patients');
	}
}
