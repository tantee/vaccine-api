<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterStocksCardsAddPatient extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stocks_cards', function (Blueprint $table) {
            $table->integer('stockFrom')->change();
            $table->integer('stockTo')->nullable()->change();
            $table->integer('amount')->default(0)->change();

            $table->string('cardType')->after('id');
            $table->string('description')->nullable()->after('cardType');
            $table->string('lotNo')->nullable()->after('stockTo');
            $table->datetime('expiryDate')->nullable()->after('lotNo');
            $table->decimal('unitCost',10,2)->nullable()->after('expiryDate');

            $table->string('hn')->nullable()->after('amount');
            $table->string('encounterId')->nullable()->after('hn');
            $table->integer('prescriptionId')->nullable()->after('encounterId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stocks_cards', function (Blueprint $table) {
            $table->string('stockFrom')->change();
            $table->string('stockTo')->change();
            $table->integer('amount')->change();
            $table->dropColumn(['hn','encounterId','prescriptionId','lotNo','description','cardType']);
        });
    }
}
