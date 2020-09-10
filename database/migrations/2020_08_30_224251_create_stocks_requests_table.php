<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStocksRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stocks_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamp('requestDispensingDate')->useCurrent();
            $table->integer('stockTo');
            $table->integer('stockFrom');
            $table->json('requestData');
            $table->string('status')->default('new')->index();
            $table->json('statusLog')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->softDeletes();
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
        Schema::dropIfExists('stocks_requests');
    }
}
