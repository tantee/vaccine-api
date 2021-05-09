<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMophApiSents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('moph_api_sents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('documentId')->index();
            $table->json('requestData');
            $table->json('responseData');
            $table->boolean('isSuccess');
            $table->timestamps();
            $table->index(['documentId','isSuccess']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('moph_api_sents');
    }
}
