<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEncountersAddAutoTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->json('autoCharge')->nullable()->after('summary');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->dropColumn(['autoCharge']);
        });
    }
}