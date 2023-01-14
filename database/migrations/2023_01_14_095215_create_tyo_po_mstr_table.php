<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTyoPoMstrTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_ems2')->create('TYO_PO_MSTR', function (Blueprint $table) {
            $table->id();
            $table->string('TPM_ITMCD');
            $table->string('TPM_ORDERNO');
            $table->date('TPM_DLVDT');
            $table->string('TPM_STATUS');
            $table->integer('TPM_ORDERQTY');
            $table->datetime('TPM_CSVOUTDT');
            $table->datetime('TPM_ORDER_CRTDT');
            $table->datetime('TPM_ORDER_REGDT');
            $table->decimal('TPM_PRC');
            $table->date('TPM_RPLY_DEADLNDT')->nullable();
            $table->string('TPM_STOREID')->nullable();
            $table->date('TPM_ISSDT')->nullable();
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
        Schema::connection('sqlsrv_ems2')->dropIfExists('TYO_PO_MSTR');
    }
}
