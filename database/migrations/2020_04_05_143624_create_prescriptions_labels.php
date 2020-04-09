<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePrescriptionsLabels extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prescriptions_labels', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('prescriptionId');
            $table->string('productCode');
            $table->json('direction')->nullable();
            $table->json('caution')->nullable();
            $table->string('productText');
            $table->text('directionText');
            $table->text('cautionText');
            $table->integer('amount');
            $table->string('shelfLocation');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->SoftDeletes();
            $table->timestamps();
            $table->index(['prescriptionId']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prescriptions_labels');
    }
}
