<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('assets', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('hn');
			$table->string('assetType')->nullable();
			$table->string('mimeType');
			$table->string('storage')->default('local');
			$table->string('storagePath')->nullable();
			$table->string('md5hash');
			$table->string('created_by')->nullable();
			$table->string('updated_by')->nullable();
			$table->string('deleted_by')->nullable();
			$table->softDeletes();
			$table->timestamps();
			$table->index(['hn','assetType']);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('assets');
	}
}
