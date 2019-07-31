<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMasterGroups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('master_groups', function (Blueprint $table) {
          $table->string('groupKey')->primary();
          $table->string('groupName');
          $table->string('description')->nullable();
          $table->text('propertiesTemplate')->nullable();
          $table->json('defaultProperties')->nullable();
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
        Schema::dropIfExists('master_groups');
    }
}
