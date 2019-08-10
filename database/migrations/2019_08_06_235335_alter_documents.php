<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterDocuments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['parentId','copyId']);
            $table->json('revision')->nullable()->after('note');
            $table->string('status')->default('draft')->after('note');
            $table->string('folder')->default('default')->after('category');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('documents', function (Blueprint $table) {
            //
            $table->dropColumn(['revision','status','folder']);
            $table->integer('parentId')->nullable()->after('templateCode'); 
            $table->string('copyId')->nullable()->after('parentId'); 
        });
    }
}
