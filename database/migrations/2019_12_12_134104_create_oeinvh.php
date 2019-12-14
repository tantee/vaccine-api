<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOeinvh extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('export')->create('OEINVH', function (Blueprint $table) {
            $table->string('DOCNUM',12)->primary();;
            $table->string('DOCDAT',8);
            $table->string('DEPCOD',4)->nullable();
            $table->string('SLMCOD',10)->nullable();
            $table->string('CUSCOD',10);
            $table->string('YOUREF',30)->nullable();
            $table->integer('PAYTRM')->nullable();
            $table->string('DUEDAT',8)->nullable();
            $table->string('NXTSEQ',3);
            $table->decimal('AMOUNT',10,2);
            $table->string('DISC',10)->default('-');
            $table->decimal('DISCAMT',10,2)->default(0);
            $table->decimal('TOTAL',10,2);
            $table->decimal('VATRAT',5,2)->default(0);
            $table->decimal('VATAMT',10,2)->default(0);
            $table->decimal('NETAMT',10,2);
            $table->string('CUSNAM',60);
            $table->string('AREACOD',4)->nullable();
            $table->string('DOCSTAT',1)->default('N');
            $table->string('NOTE1',50)->nullable();
            $table->string('NOTE2',50)->nullable();  
            $table->string('NOTE3',50)->nullable();  
            $table->string('NOTE4',50)->nullable();  
            $table->string('NOTE5',50)->nullable();
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
        Schema::dropIfExists('OEINVH');
    }
}
