<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStocksCardsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('stocks_cards', function(Blueprint $table)
		{
			$table->increments('id');
			$table->timestamp('cardDateTime')->useCurrent();
			$table->string('cardType');
			$table->string('description')->nullable();
			$table->string('productCode');
			$table->integer('stockFrom')->nullable();
			$table->integer('stockTo')->nullable();
			$table->string('lotNo')->nullable();
			$table->dateTime('expiryDate')->nullable();
			$table->decimal('unitCost', 10)->nullable();
			$table->integer('quantity')->default(0);
			$table->string('hn')->nullable();
			$table->string('encounterId')->nullable();
			$table->integer('prescriptionsDispensingId')->nullable();
			$table->integer('encountersDispensingId')->nullable();
			$table->integer('documentId')->nullable();
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
		Schema::drop('stocks_cards');
	}
}
