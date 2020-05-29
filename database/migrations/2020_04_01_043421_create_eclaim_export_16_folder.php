<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEclaimExport16Folder extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('export')->create('ECLAIM_INS', function (Blueprint $table) {
            $table->increments('id');
            $table->string('HN',15);
            $table->string('INSCL',3)->default('UCS');
            $table->string('SUBTYPE',2)->nullable();
            $table->string('CID',16)->nullable();
            $table->string('DATEIN',8)->nullable();
            $table->string('DATEEXP',8)->nullable();
            $table->string('HOSPMAIN',5);
            $table->string('HOSPSUB',5)->nullable();
            $table->string('GOVCODE',6)->nullable();
            $table->string('GOVNAME',255)->nullable();
            $table->string('PERMITNO',13)->nullable();
            $table->string('DOCNO',30)->nullable();
            $table->string('OWNRPID',13)->nullable();
            $table->string('OWNNAME',255)->nullable();
            $table->string('AN',15)->nullable();
            $table->string('SEQ',15);
            $table->string('SUBINSCL',2)->nullable();
            $table->string('RELINSCL',1)->nullable();
            $table->string('HTYPE',1)->nullable();
            $table->datetime('batch');
            $table->timestamps();
        });

        Schema::connection('export')->create('ECLAIM_PAT', function (Blueprint $table) {
            $table->increments('id');
            $table->string('HCODE',5);
            $table->string('HN',15);
            $table->string('CHANGWAT',2)->nullable();
            $table->string('AMPHUR',2)->nullable();
            $table->string('DOB',8);
            $table->string('SEX',1);
            $table->string('MARRIAGE',1);
            $table->string('OCCUPA',3);
            $table->string('NATION',3);
            $table->string('PERSON_ID',13);
            $table->string('NAMEPAT',36);
            $table->string('TITLE',30)->nullable();
            $table->string('FNAME',40)->nullable();
            $table->string('LNAME',40)->nullable();
            $table->string('IDTYPE',1);       
            $table->timestamps();
        });

        Schema::connection('export')->create('ECLAIM_OPD', function (Blueprint $table) {
            $table->increments('id');
            $table->string('HN',15);
            $table->string('CLINIC',4)->nullable();
            $table->string('DATEOPD',8);
            $table->string('TIMEOPD',4);
            $table->string('SEQ',15);
            $table->string('UUC',1)->default('1');
            $table->timestamps();
        });

        Schema::connection('export')->create('ECLAIM_ORF', function (Blueprint $table) {
            $table->increments('id');
            $table->string('HN',15);
            $table->string('DATEOPD',8);
            $table->string('CLINIC',4)->nullable();
            $table->string('REFER',5);
            $table->string('REFERTYPE',1)->default('1');
            $table->string('SEQ',15);
            $table->timestamps();
        });

        Schema::connection('export')->create('ECLAIM_ODX', function (Blueprint $table) {
            $table->increments('id');
            $table->string('HN',15);
            $table->string('DATEDX',8);
            $table->string('CLINIC',4)->nullable();
            $table->string('DIAG',7);
            $table->string('DXTYPE',1);
            $table->string('DRDX',6);
            $table->string('PERSON_ID',13);
            $table->string('SEQ',15);
            $table->timestamps();
        });

        Schema::connection('export')->create('ECLAIM_OOP', function (Blueprint $table) {
            $table->increments('id');
            $table->string('HN',15);
            $table->string('DATEOPD',8);
            $table->string('CLINIC',4)->nullable();
            $table->string('OPER',7);
            $table->string('DROPID',6);
            $table->string('PERSON_ID',13);
            $table->string('SEQ',15);
            $table->timestamps();
        });

        Schema::connection('export')->create('ECLAIM_IPD', function (Blueprint $table) {
            $table->increments('id');
            $table->string('HN',15);
            $table->string('AN',15);
            $table->string('DATEADM',8);
            $table->string('TIMEADM',4);
            $table->string('DATEDSC',8);
            $table->string('TIMEDSC',4);
            $table->string('DISCHS',1);
            $table->string('DISCHT',1);
            $table->string('WARDDSC',4)->nullable();
            $table->string('DEPT',2)->nullable();
            $table->decimal('ADM_W',5,2);
            $table->string('UUC',1)->default('1');
            $table->string('SVCTYPE',1)->default('A');
            $table->timestamps();
        });

        Schema::connection('export')->create('ECLAIM_IRF', function (Blueprint $table) {
            $table->increments('id');
            $table->string('AN',15);
            $table->string('REFER',5);
            $table->string('REFERTYPE',1);
            $table->timestamps();
        });

        Schema::connection('export')->create('ECLAIM_IDX', function (Blueprint $table) {
            $table->increments('id');
            $table->string('AN',15);
            $table->string('DIAG',7);
            $table->string('DXTYPE',1);
            $table->string('DRDX',6);
            $table->timestamps();
        });

        Schema::connection('export')->create('ECLAIM_IOP', function (Blueprint $table) {
            $table->increments('id');
            $table->string('AN',15);
            $table->string('OPER',7);
            $table->string('OPTYPE',1)->nullable();
            $table->string('DROPID',6);
            $table->string('DATEIN',8);
            $table->string('TIMEIN',4);
            $table->string('DATEOUT',8)->nullable();
            $table->string('TIMEOUT',4)->nullable();
            $table->timestamps();
        });

        Schema::connection('export')->create('ECLAIM_CHT', function (Blueprint $table) {
            $table->increments('id');
            $table->string('HN',15);
            $table->string('AN',15)->nullable();
            $table->string('DATE',8);
            $table->decimal('TOTAL',12,2);
            $table->decimal('PAID',12,2);
            $table->string('PTTYPE',2)->default('10');
            $table->string('PERSON_ID',13)->nullable();
            $table->string('SEQ',15);
            $table->timestamps();
        });

        Schema::connection('export')->create('ECLAIM_CHA', function (Blueprint $table) {
            $table->increments('id');
            $table->string('HN',15);
            $table->string('AN',15)->nullable();
            $table->string('DATE',8);
            $table->string('CHRGITEM',2);
            $table->decimal('AMOUNT',12,2);
            $table->string('PERSON_ID',13)->nullable();
            $table->string('SEQ',15);
            $table->timestamps();
        });

        Schema::connection('export')->create('ECLAIM_AER', function (Blueprint $table) {
            $table->increments('id');
            $table->string('HN',15);
            $table->string('AN',15)->nullable();
            $table->string('DATEOPD',8);
            $table->string('AUTHAE',12)->nullable();
            $table->string('AEDATE',8);
            $table->string('AETIME',4);
            $table->string('AETYPE',1);
            $table->string('REFER_NO',20);
            $table->string('REFMAINI',5);
            $table->string('IREFTYPE',4);
            $table->string('REFMAINO',5);
            $table->string('OREFTYPE',4);
            $table->string('UCAE',1);
            $table->string('EMTYPE',1);
            $table->string('SEQ',15);
            $table->string('AESTATUS',1)->nullable();
            $table->string('DALERT',8)->nullable();
            $table->string('TALERT',4)->nullable();
            $table->timestamps();
        });

        Schema::connection('export')->create('ECLAIM_ADP', function (Blueprint $table) {
            $table->increments('id');
            $table->string('HN',15);
            $table->string('AN',15)->nullable();
            $table->string('DATEOPD',8);
            $table->string('TYPE',2);
            $table->string('CODE',11);
            $table->decimal('QTY',4,0);
            $table->decimal('RATE',12,2);
            $table->string('SEQ',15);
            $table->string('CAGCODE',10)->nullable();
            $table->string('DOSE',10)->nullable();
            $table->string('CA_TYPE',1)->default('V');
            $table->string('SERIALNO',24)->nullable();
            $table->decimal('TOTCOPAY',12,2)->nullable();
            $table->string('USE_STATUS',1)->nullable();
            $table->decimal('TOTAL',12,2)->nullable();
            $table->decimal('QTYDAY',3,0)->nullable();
            $table->timestamps();
        });

        Schema::connection('export')->create('ECLAIM_LVD', function (Blueprint $table) {
            $table->increments('id');
            $table->string('SEQLVD',3);
            $table->string('AN',15);
            $table->string('DATEOUT',8);
            $table->string('TIMEOUT',4);
            $table->string('DATEIN',8);
            $table->string('TIMEIN',4);
            $table->string('QTYDAY',3);
            $table->timestamps();
        });

        Schema::connection('export')->create('ECLAIM_DRU', function (Blueprint $table) {
            $table->increments('id');
            $table->string('HCODE',5)->nullable();
            $table->string('HN',15);
            $table->string('AN',15)->nullable();
            $table->string('CLINIC',4)->nullable();
            $table->string('PERSON_ID',13)->nullable();
            $table->string('DATE_SERV',8);
            $table->string('DID',30);
            $table->string('DIDNAME',255);
            $table->string('AMOUNT',12);
            $table->string('DRUGPRIC',14);
            $table->string('DRUGCOST',14)->nullable();
            $table->string('DIDSTD',24)->nullable();
            $table->string('UNIT',20)->nullable();
            $table->string('UNIT_PACK',20)->nullable();
            $table->string('SEQ',15);
            $table->string('DRUGREMARK',2);
            $table->string('PA_NO',9);
            $table->decimal('TOTCOPAY',12,2)->nullable();
            $table->string('USE_STATUS',1)->nullable();
            $table->decimal('TOTAL',12,2)->nullable();
            $table->string('SIGCODE',50)->nullable();
            $table->string('SIGTEXT',255)->nullable();
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
        Schema::dropIfExists('ECLAIM_INS');
        Schema::dropIfExists('ECLAIM_PAT');
        Schema::dropIfExists('ECLAIM_OPD');
        Schema::dropIfExists('ECLAIM_ORF');
        Schema::dropIfExists('ECLAIM_ODX');
        Schema::dropIfExists('ECLAIM_OOP');
        Schema::dropIfExists('ECLAIM_IPD');
        Schema::dropIfExists('ECLAIM_IRF');
        Schema::dropIfExists('ECLAIM_IDX');
        Schema::dropIfExists('ECLAIM_IOP');
        Schema::dropIfExists('ECLAIM_CHT');
        Schema::dropIfExists('ECLAIM_CHA');
        Schema::dropIfExists('ECLAIM_AER');
        Schema::dropIfExists('ECLAIM_ADP');
        Schema::dropIfExists('ECLAIM_LVD');
        Schema::dropIfExists('ECLAIM_DRU');
    }
}
