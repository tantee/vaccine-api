<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentsTemplatesTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('documents_templates', function(Blueprint $table)
		{
			$table->string('templateCode', 50)->unique();
			$table->string('templateName');
			$table->string('templateCompatibility')->nullable();
			$table->string('revisionId');
			$table->date('revisionDate');
			$table->string('description')->nullable();
			$table->string('defaultCategory', 50)->nullable();
			$table->boolean('isRequiredPatientInfo')->nullable()->default(1);
			$table->boolean('isRequiredEncounter')->nullable()->default(1);
			$table->text('editTemplate')->nullable();
			$table->text('viewTemplate')->nullable();
			$table->string('printTemplate')->nullable();
			$table->text('templateScript')->nullable();
			$table->boolean('isPrintable')->nullable()->default(1);
			$table->boolean('isNoDefaultHeader')->nullable()->default(0);
			$table->boolean('isNoDefaultFooter')->nullable()->default(0);
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
		Schema::drop('documents_templates');
	}
}
