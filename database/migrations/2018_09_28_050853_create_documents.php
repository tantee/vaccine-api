<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDocuments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->increments('id');
            $table->string('hn');
            $table->string('encounterId')->nullable();
            $table->string('referenceId')->nullable(); //such as receipt id
            $table->string('templateCode');
            $table->integer('parentId')->nullable(); //wil remove in future release
            $table->string('copyId')->nullable(); //wil remove in future release
            $table->string('category')->default('999');
            $table->boolean('isScanned')->default(false);
            $table->json('data');
            $table->text('note')->nullable();
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
        Schema::dropIfExists('documents');
    }
}
