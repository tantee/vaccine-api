<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApis extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('apis', function (Blueprint $table) {
            $table->increments('id');
            $table->string('apiMethod');
            $table->string('apiRoute');
            $table->string('scope')->nullable();
            $table->string('permission')->nullable();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('sourceApiMethod')->nullable();
            $table->string('sourceApiUrl')->nullable();
            $table->text('ETLCode')->nullable();
            $table->text('ETLCodeError')->nullable();
            $table->boolean('isFlatten')->default(false);
            $table->boolean('isMaskError')->default(false);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->SoftDeletes();
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
        Schema::dropIfExists('apis');
    }
}
