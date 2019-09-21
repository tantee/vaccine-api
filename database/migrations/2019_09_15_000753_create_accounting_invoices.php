<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountingInvoices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounting_invoices', function (Blueprint $table) {
            $table->string('invoiceId')->primary();
            $table->string('hn',20);
            $table->integer('patientsInsurancesId')->nullable();
            $table->decimal('amountDue',10,2);
            $table->decimal('amountPaid',10,2)->default(0);
            $table->integer('documentId')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->SoftDeletes();
            $table->timestamps();
            $table->index(['hn']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounting_invoices');
    }
}
