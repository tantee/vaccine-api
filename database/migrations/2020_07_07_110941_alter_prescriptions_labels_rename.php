<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterPrescriptionsLabelsRename extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('prescriptions_labels', function (Blueprint $table) {
            $table->renameColumn('amount', 'quantity');
            $table->renameColumn('direction', 'directions');
            $table->renameColumn('caution', 'cautions');
            $table->string('productText')->nullable()->change();
            $table->string('directionText')->nullable()->change();
            $table->string('cautionText')->nullable()->change();
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
            $table->renameColumn('quantity', 'amount');
            $table->renameColumn('directions', 'direction');
            $table->renameColumn('cautions', 'caution');
            $table->string('productText')->change();
            $table->string('directionText')->change();
            $table->string('cautionText')->change();
        });
    }
}
