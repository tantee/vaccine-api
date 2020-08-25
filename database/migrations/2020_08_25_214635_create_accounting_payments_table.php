<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountingPaymentsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('accounting_payments', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('cashiersPeriodsId')->index();
			$table->string('receiptId', 50)->index();
			$table->string('invoiceId', 50);
			$table->string('paymentMethod', 50)->index();
			$table->string('paymentDetail')->nullable();
			$table->string('paymentAccount')->nullable();
			$table->decimal('amountDue', 10);
			$table->decimal('amountPaid', 10);
			$table->integer('documentId')->nullable();
			$table->string('note')->nullable();
			$table->boolean('isVoid')->default(0);
			$table->dateTime('isVoidDateTime')->nullable();
			$table->integer('isVoidCashiersPeriodsId')->nullable();
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
		Schema::drop('accounting_payments');
	}
}
