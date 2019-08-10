<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterDocumentsTemplates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('documents_templates', function (Blueprint $table) {
            $table->boolean('isRequiredPatientInfo')->default(true)->change();
            $table->boolean('isNoDefaultHeader')->default(false)->after('isPrintable');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('documents_templates', function (Blueprint $table) {
            $table->boolean('isRequiredPatientInfo')->default(false)->change();
            $table->dropColumn(['isNoDefaultHeader']);
        });
    }
}
