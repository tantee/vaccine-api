<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDocumentsTemplates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documents_templates', function (Blueprint $table) {
            $table->string('templateCode')->unique();
            $table->string('templateName');
            $table->string('templateCompatibility')->nullable();
            $table->string('description')->nullable();
            $table->string('defaultCategory')->nullable();
            $table->boolean('isRequiredPatientInfo')->default(false);
            $table->boolean('isRequiredEncounter')->default(false);
            $table->text('editTemplate')->nullable();
            $table->text('viewTemplate')->nullable();
            $table->string('printTemplate')->nullable();
            $table->boolean('isPrintable')->default(true);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->SoftDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('documents_templates');
    }
}
