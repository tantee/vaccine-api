<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterDocumentsTemplatesRevision extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('documents_templates', function (Blueprint $table) {
            //
            $table->string('revisionId')->after('templateCompatibility');
            $table->string('revisionDate')->after('revisionId');
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
            //
            $table->dropColumn(['revisionId','revisionDate']);
        });
    }
}
