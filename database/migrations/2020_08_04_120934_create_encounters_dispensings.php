<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEncountersDispensings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('encounters_dispensings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('encounterId');
            $table->string('productCode');
            $table->integer('quantity');
            $table->integer('stockId')->nullable();
            $table->string('lotNo')->nullable();
            $table->integer('transactionId')->nullable();
            $table->boolean('isNotCharge')->default(false);
            $table->string('status')->default('prepared');
            $table->json('statusLog')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->SoftDeletes();
            $table->timestamps();
            $table->index(['encounterId']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('encounters_dispensings');
    }
}
