<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRadiologiesTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('radiologies', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('hn');
			$table->string('accessionNumber')->nullable();
			$table->string('modality');
			$table->string('description')->nullable();
			$table->dateTime('studyDateTime');
			$table->dateTime('requestDateTime')->nullable();
			$table->dateTime('reportDateTime')->nullable();
			$table->string('uid');
			$table->string('referringDoctor')->nullable();
			$table->string('reportingDoctorCode')->nullable();
			$table->string('reportingType', 50)->nullable();
			$table->integer('imageCount')->default(0);
			$table->string('requestDocumentId')->nullable();
			$table->string('reportDocumentId')->nullable();
			$table->string('productCode', 50)->nullable();
			$table->integer('transactionId')->nullable();
			$table->string('status')->default('COMPLETED');
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
		Schema::drop('radiologies');
	}
}
