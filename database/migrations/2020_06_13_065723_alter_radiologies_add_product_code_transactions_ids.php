<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterRadiologiesAddProductCodeTransactionsIds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('radiologies', function (Blueprint $table) {
            $table->string('reportingType',50)->nullable()->after('reportingDoctor');
            $table->string('productCode',50)->nullable()->after('reportDocumentId');
            $table->integer('transactionId')->nullable()>after('productCode');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('radiologies', function (Blueprint $table) {
            $table->dropColumn(['reportingType','productCode','transactionId']);
        });
    }
}
