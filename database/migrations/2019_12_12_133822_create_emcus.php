<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmcus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('export')->create('EMCUS', function (Blueprint $table) {
            $table->increments('id');
            $table->string('CUSCOD',10);
            $table->string('CUSTYP',2);
            $table->string('PRENAM',15);
            $table->string('CUSNAM',60);
            $table->string('ADDR01',50)->nullable();
            $table->string('ADDR02',50)->nullable();
            $table->string('ADDR03',30)->nullable();
            $table->string('ZIPCOD',5)->nullable();
            $table->string('TELNUM',50)->nullable();
            $table->string('TAXID',15)->nullable();
            $table->integer('ORGNUM')->default(0);
            $table->string('CONTACT',40)->nullable();
            $table->string('SHIPTO',10)->nullable();
            $table->string('SLMCOD',10)->nullable();
            $table->string('AREACOD',2)->nullable();
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
        Schema::dropIfExists('EMCUS');
    }
}
