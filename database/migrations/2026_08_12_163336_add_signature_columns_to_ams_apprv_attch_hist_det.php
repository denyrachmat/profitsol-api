<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::connection('sqlsrv_ams')->table('ams_apprv_attch_hist_det', function (Blueprint $table) {
            $table->string('amaad_signed_by')->nullable()->after('amaad_dl_link')->comment('Username who signed this version');
            $table->timestamp('amaad_signed_at')->nullable()->after('amaad_signed_by')->comment('When signature was applied');
            $table->text('amaad_signatures')->nullable()->after('amaad_signed_at')->comment('JSON array of all signatures applied [{username, signature_base64, x, y, page, signed_at}]');
        });
    }

    public function down()
    {
        Schema::connection('sqlsrv_ams')->table('ams_apprv_attch_hist_det', function (Blueprint $table) {
            $table->dropColumn(['amaad_signed_by', 'amaad_signed_at', 'amaad_signatures']);
        });
    }
};
