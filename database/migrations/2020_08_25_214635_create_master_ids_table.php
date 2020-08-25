<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasterIdsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('master_ids', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('idType');
			$table->string('prefix');
			$table->integer('runningNumber');
			$table->timestamps();
			$table->index(['idType','prefix']);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('master_ids');
	}
}
