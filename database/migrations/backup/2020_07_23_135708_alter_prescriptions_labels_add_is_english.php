<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterPrescriptionsLabelsAddIsEnglish extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('prescriptions_labels', function (Blueprint $table) {
            $table->boolean('isEnglish')->default(false)->after('shelfLocation');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('prescriptions_labels', function (Blueprint $table) {
            $table->dropColumn(['isEnglish']);
        });
    }
}
