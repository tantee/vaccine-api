<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePatientsAllergies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patients_allergies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('hn',20);
            $table->string('allergyType');
            $table->string('suspectedProduct');
            $table->string('suspectedGPU')->nullable();
            $table->string('probability');
            $table->string('severity');
            $table->text('manifestation')->nullable();
            $table->string('informationSource');
            $table->boolean('isNewOccurence')->default(false);
            $table->date('isNewOccurenceDate')->nullable();
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
        Schema::dropIfExists('patients_allergies');
    }
}
