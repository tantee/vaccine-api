<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEncountersAutochargesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('encounters_autocharges', function (Blueprint $table) {
            $table->increments('id');
            $table->string('encounterId', 50);
            $table->string('productCode', 50);
            $table->integer('quantity')->nullable()->default(1);
            $table->integer('repeatHour')->nullable();
            $table->integer('roundHour')->nullable();
            $table->integer('limitPerEncounter')->nullable();
            $table->integer('limitPerDay')->nullable();
            $table->boolean('isActive')->default(1)->index();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->softDeletes();
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
        Schema::dropIfExists('encounters_autocharges');
    }
}
