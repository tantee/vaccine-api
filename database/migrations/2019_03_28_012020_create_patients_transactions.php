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
            $table->string('hn');
            $table->string('encounterId');
            $table->string('referenceId'); //Invoice Id? Unique ID?
            $table->string('billingId')->nullable();
            $table->string('categoryInsurance');
            $table->string('categoryCgd');
            $table->string('productCode');
            $table->integer('quantity');
            $table->decimal('unitPrice',10,2);
            $table->decimal('totalPrice',10,2);
            $table->decimal('totalCgdPrice',10,2);
            $table->string('orderPerson');
            $table->string('orderLocation');
            $table->string('performPerson')->nullable();
            $table->string('performLocation')->nullable();
            $table->boolean('isPerformed')->default(true);
            $table->boolean('isChargable')->default(true);
            $table->enum('status',['confirmed','canceled','drafted'])->default('confirmed');
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
