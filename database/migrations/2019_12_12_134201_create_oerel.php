<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOerel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('export')->create('OEREL', function (Blueprint $table) {
            $table->increments('id');
            $table->string('DOCNUM',12);
            $table->string('DOCDAT',8);
            $table->string('IVNUM',12);
            $table->decimal('AMOUNT',10,2);
            $table->string('PAYTYP',10);
            $table->string('PAYNOTE',120)->nullable();
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
        Schema::dropIfExists('OEREL');
    }
}
