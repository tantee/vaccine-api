<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCashiersPeriods extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cashiers_periods', function (Blueprint $table) {
            $table->increments('id');
            $table->string('cashierId',20);
            $table->datetime('startDateTime');
            $table->datetime('endDateTime')->nullable();
            $table->decimal('initialCash',10,2);
            $table->decimal('finalCash',10,2)->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->SoftDeletes();
            $table->timestamps();
            $table->index(['cashierId']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cashiers_periods');
    }
}
