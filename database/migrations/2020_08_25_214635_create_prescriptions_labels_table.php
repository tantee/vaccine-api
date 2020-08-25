<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrescriptionsLabelsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('prescriptions_labels', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('prescriptionId')->index();
			$table->string('productCode');
			$table->json('directions')->nullable();
			$table->json('cautions')->nullable();
			$table->string('productText')->nullable();
			$table->string('directionText')->nullable();
			$table->string('cautionText')->nullable();
			$table->integer('quantity');
			$table->string('shelfLocation')->nullable();
			$table->boolean('isEnglish')->default(0);
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
		Schema::drop('prescriptions_labels');
	}
}
