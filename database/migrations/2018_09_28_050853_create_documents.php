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
            $table->string('hn',20);
            $table->string('encounterId',50)->nullable();
            $table->string('referenceId',50)->nullable(); //such as receipt id
            $table->string('templateCode',50);
            $table->string('category',50)->nullable()->default('999');
            $table->string('folder',20)->nullable()->default('default');
            $table->boolean('isScanned')->default(false);
            $table->json('data')->nullable();
            $table->text('note')->nullable();
            $table->string('status')->default('draft');
            $table->json('revision')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->SoftDeletes();
            $table->timestamps();
            $table->index(['hn','encounterId','templateCode']);
            $table->index(['hn','encounterId','category','folder']);
            $table->index(['folder','referenceId']);
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
