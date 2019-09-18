<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountingPayments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounting_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('cashiersPeriodsId');
            $table->string('receiptId',50);
            $table->string('invoiceId',50);
            $table->string('paymentMethod',50);
            $table->string('paymentDetail')->nullable();
            $table->string('paymentAccount')->nullable();
            $table->decimal('amountDue',10,2);
            $table->decimal('amountPaid',10,2);
            $table->integer('documentId')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->SoftDeletes();
            $table->timestamps();
            $table->index(['receiptId']);
            $table->index(['cashiersPeriodsId']);
            $table->index(['paymentMethod']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounting_payments');
    }
}
