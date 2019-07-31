<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdmissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('admissions', function (Blueprint $table) {
            $table->string('admissionId')->primary();
            $table->string('hn');
            $table->string('currentLocation');
            $table->string('currentDoctorCode');
            $table->datetime('startDateTime');
            $table->datetime('endDateTime')->nullable();
            $table->json('diagnosis')->nullable();
            $table->json('summary')->nullable();
            $table->json('locationLog')->nullable();
            $table->enum('status',['admitted','discharged','closed']);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->SoftDeletes();
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
        Schema::dropIfExists('admissions');
    }
}
