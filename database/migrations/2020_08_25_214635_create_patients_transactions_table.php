<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientsTransactionsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('patients_transactions', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('parentTransactionId')->nullable();
			$table->string('hn', 20);
			$table->string('encounterId', 50);
			$table->integer('prescriptionId')->nullable()->index();
			$table->string('invoiceId', 50)->nullable();
			$table->timestamp('transactionDateTime')->useCurrent();
			$table->string('categoryInsurance', 50)->nullable();
			$table->string('categoryCgd', 50)->nullable();
			$table->string('productCode', 50);
			$table->integer('quantity')->nullable()->default(1);
			$table->decimal('overridePrice', 10)->nullable();
			$table->decimal('overrideDiscount', 5)->nullable();
			$table->integer('soldPatientsInsurancesId')->nullable();
			$table->string('soldInsuranceCode', 50)->nullable();
			$table->decimal('soldPrice', 10)->nullable();
			$table->decimal('soldDiscount', 5)->nullable();
			$table->decimal('soldTotalPrice', 10)->nullable();
			$table->decimal('soldTotalDiscount', 10)->nullable();
			$table->decimal('soldFinalPrice', 10)->nullable();
			$table->string('orderDoctorCode')->nullable();
			$table->string('orderClinicCode')->nullable();
			$table->string('orderLocationCode')->nullable();
			$table->string('performDoctorCode')->nullable();
			$table->string('performClinicCode')->nullable();
			$table->string('performLocationCode')->nullable();
			$table->boolean('isForceSelfPay')->default(0);
			$table->boolean('isPerformed')->default(1);
			$table->boolean('isChargable')->default(1);
			$table->boolean('isRevised')->default(0);
			$table->json('itemizedProducts')->nullable();
			$table->string('status')->nullable()->default('confirmed');
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
		Schema::drop('patients_transactions');
	}
}
