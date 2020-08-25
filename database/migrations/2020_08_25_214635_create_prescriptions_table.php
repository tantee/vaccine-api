<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrescriptionsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('prescriptions', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('hn', 20);
			$table->string('encounterId', 50);
			$table->integer('documentId')->nullable();
			$table->timestamp('scheduleDate')->useCurrent();
			$table->string('doctorCode')->nullable();
			$table->string('status')->default('new')->index();
			$table->json('statusLog')->nullable();
			$table->string('created_by')->nullable();
			$table->string('updated_by')->nullable();
			$table->string('deleted_by')->nullable();
			$table->softDeletes();
			$table->timestamps();
			$table->index(['hn','encounterId']);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('prescriptions');
	}
}
