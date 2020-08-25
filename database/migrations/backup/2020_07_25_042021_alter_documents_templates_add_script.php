<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterDocumentsTemplatesAddScript extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('documents_templates', function (Blueprint $table) {
            $table->text('templateScript')->nullable()->after('printTemplate');
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
            $table->dropColumn(['templateScript']);
        });
    }
}
