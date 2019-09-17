<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePatientsTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patients_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('hn',20);
            $table->string('encounterId',50);
            $table->string('invoiceId',50)->nullable(); //Invoice Id? Unique ID?
            $table->timestamp('transactionDateTime')->useCurrent();
            $table->string('categoryInsurance',50)->nullable();
            $table->string('categoryCgd',50)->nullable();
            $table->string('productCode',50);
            $table->integer('quantity')->nullable()->default(1);
            $table->integer('soldPatientsInsurancesId')->nullable();
            $table->decimal('soldPrice',10,2)->nullable();
            $table->decimal('soldDiscount',5,2)->nullable();
            $table->decimal('soldTotalPrice',10,2)->nullable();
            $table->decimal('soldTotalDiscount',10,2)->nullable();
            $table->decimal('soldFinalPrice',10,2)->nullable();
            $table->string('orderDoctorCode')->nullable();
            $table->string('orderClinicCode')->nullable();
            $table->string('orderLocationCode')->nullable();
            $table->string('performDoctorCode')->nullable();
            $table->string('performClinicCode')->nullable();
            $table->string('performLocationCode')->nullable();
            $table->boolean('isForceSelfPay')->default(false);
            $table->boolean('isPerformed')->default(true);
            $table->boolean('isChargable')->default(true);
            $table->string('status')->nullable()->default('confirmed');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->SoftDeletes();
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
        Schema::dropIfExists('patients_transactions');
    }
}
