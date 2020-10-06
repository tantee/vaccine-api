<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePatientsMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patients_messages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('hn', 20)->index();
            $table->string('importantLevel');
            $table->timestamp('beginDateTime')->useCurrent();
            $table->datetime('endDateTime')->nullable();
            $table->json('locations')->nullable();
            $table->text('message');
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
        Schema::dropIfExists('patients_messages');
    }
}
