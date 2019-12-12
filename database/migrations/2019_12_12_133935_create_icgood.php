<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIcgood extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('export')->create('ICGOOD', function (Blueprint $table) {
            $table->increments('id');
            $table->string('STKCOD',20);
            $table->string('STKTH',50);
            $table->string('STKEN',50);
            $table->string('STKGRP',4);
            $table->string('QUCOD',2);
            $table->string('STKUNIT',2);
            $table->decimal('SELLPR1',10,2);
            $table->decimal('SELLPR2',10,2)->nullable();
            $table->decimal('SELLPR3',10,2)->nullable();
            $table->decimal('SELLPR4',10,2)->nullable();
            $table->string('STKTYP',1);
            $table->string('REMARK',50)->nullable();
            $table->datetime('batch');
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
        Schema::dropIfExists('ICGOOD');
    }
}
