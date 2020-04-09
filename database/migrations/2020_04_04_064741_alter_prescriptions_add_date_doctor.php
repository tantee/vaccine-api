<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterPrescriptionsAddDateDoctor extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn(['dispensing','labels']);
            $table->string('status')->default('new')->change();
            $table->timestamp('scheduleDate')->useCurrent()->after('documentId');
            $table->string('doctorCode')->after('scheduleDate');
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn(['scheduleDate','doctorCode']);
            $table->json('labels')->nullable()->after('documentId');
            $table->json('dispensing')->nullable()->after('labels');
            $table->dropIndex(['status']);
        });
    }
}
