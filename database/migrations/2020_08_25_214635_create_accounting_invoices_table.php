<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountingInvoicesTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('accounting_invoices', function(Blueprint $table)
		{
			$table->string('invoiceId')->primary();
			$table->string('hn', 20)->index();
			$table->integer('cashiersPeriodsId')->nullable();
			$table->integer('patientsInsurancesId')->nullable();
			$table->decimal('amount', 10);
			$table->decimal('amountDue', 10);
			$table->decimal('amountPaid', 10)->default(0.00);
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
		Schema::drop('accounting_invoices');
	}
}
