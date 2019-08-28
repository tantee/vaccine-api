<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEncounters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->dropColumn(['startDateTime','endDateTime','status']);
            $table->string('locationCode')->after('doctorCode');
            $table->string('locationSubunitCode')->nullable()->after('locationCode');
            $table->json('locationLog')->nullable()->after('locationSubunitCode');
            $table->datetime('admitDateTime')->after('locationSubunitCode');
            $table->datetime('dischargeDateTime')->nullable()->after('admitDateTime');
            $table->json('summary')->nullable()->after('diagnosis');
            $table->string('status')->nullable()->default('checkedIn');
            $table->json('statusLog')->nullable()->after('status');
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
            $table->dropColumn(['admitDateTime','dischargeDateTime','status','locationCode','locationSubunitCode','summary','locationLog','statusLog']);
            $table->datetime('startDateTime')->after('doctorCode');
            $table->datetime('endDateTime')->nullable()->after('startDateTime');
            $table->enum('status',['checkedIn','checkedOut','closed'])->change();
        });
    }
}
