<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePatients extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->string('hn')->primary();
            $table->date('dateOfBirth');
            $table->date('dateOfDeath')->nullable();
            $table->tinyInteger('personIdType');
            $table->string('personId');
            $table->boolean('personIdVerified')->default(false);
            $table->json('personIdDetail')->nullable();
            $table->string('religion')->nullable();
            $table->string('nationality')->nullable();
            $table->string('race')->nullable();
            $table->tinyInteger('sex');
            $table->String('maritalStatus')->nullable();
            $table->String('occupation')->nullable();
            $table->String('primaryMobileNo');
            $table->String('primaryTelephoneNo')->nullable();
            $table->String('primaryEmail')->nullable();
            $table->String('maternalName')->nullable();
            $table->String('paternalName')->nullable();
            $table->String('spouseName')->nullable();
            $table->enum('classifiedLevel',['normal','vip','vvip'])->default('normal');
            $table->enum('status',['active','inactive','merged'])->default('active');
            $table->String('mergedTo')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['personIdType','personId']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patients');
    }
}
