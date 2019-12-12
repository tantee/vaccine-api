<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOestkl extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('export')->create('OESTKL', function (Blueprint $table) {
            $table->increments('id');
            $table->string('DOCNUM',12);
            $table->string('SEQNUM',3);
            $table->string('LOCCOD',4);
            $table->string('STKCOD',20);
            $table->string('STKDES',50);
            $table->decimal('TRNQTY',8,4);
            $table->string('REMARK',50)->nullable();
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
        Schema::dropIfExists('OESTKL');
    }
}
