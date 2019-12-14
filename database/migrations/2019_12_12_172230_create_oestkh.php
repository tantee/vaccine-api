<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOestkh extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('export')->create('OESTKH', function (Blueprint $table) {
            $table->string('DOCNUM',12)->primary();;
            $table->string('DOCDAT',8);
            $table->string('DEPCOD',4);
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
        Schema::dropIfExists('OESTKH');
    }
}
