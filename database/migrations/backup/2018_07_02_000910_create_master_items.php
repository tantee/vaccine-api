<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMasterItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('master_items', function (Blueprint $table) {
          $table->increments('id');
          $table->string('groupKey',50);
          $table->string('itemCode',50);
          $table->string('itemValue');
          $table->string('itemValueEN')->nullable();
          $table->integer('ordering')->default(0);
          $table->string('keyword')->nullable();
          $table->string('filterText')->nullable();
          $table->json('properties')->nullable();
          $table->string('created_by')->nullable();
          $table->string('updated_by')->nullable();
          $table->string('deleted_by')->nullable();
          $table->SoftDeletes();
          $table->timestamps();
          $table->unique(['groupKey','itemCode']);
          $table->index(['groupKey','itemCode','filterText']);
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('master_items');
    }
}
