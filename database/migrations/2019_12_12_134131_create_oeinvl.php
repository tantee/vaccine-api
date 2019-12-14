<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOeinvl extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('export')->create('OEINVL', function (Blueprint $table) {
            $table->increments('id');
            $table->string('DOCNUM',12);
            $table->string('SEQNUM',3);
            $table->string('LOCCOD',4);
            $table->string('STKCOD',20);
            $table->string('STKDES',50);
            $table->decimal('TRNQTY',8,4);
            $table->decimal('UNITPR',10,2);
            $table->string('TQUCOD',2);
            $table->string('DISC',10)->default('-');
            $table->decimal('DISCAMT',10,2)->default(0);
            $table->decimal('TRNVAL',10,2);
            $table->datetime('batch');
            $table->timestamps();
            $table->index(['DOCNUM','SEQNUM']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('OEINVL');
    }
}
