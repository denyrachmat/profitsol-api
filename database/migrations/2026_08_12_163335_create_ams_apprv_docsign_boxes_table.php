<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::connection('sqlsrv_ams')->create('ams_apprv_docsign_boxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('amsm_id')->comment('Approval Master ID');
            $table->unsignedBigInteger('amsmd_id')->comment('Approval Map Detail ID (approver step)');
            $table->integer('dsbx_page_no')->default(1)->comment('Page number in PDF (1-indexed)');
            $table->float('dsbx_x')->comment('X coordinate (pixels from left)');
            $table->float('dsbx_y')->comment('Y coordinate (pixels from top)');
            $table->float('dsbx_width')->default(150)->comment('Box width in pixels');
            $table->float('dsbx_height')->default(50)->comment('Box height in pixels');
            $table->string('dsbx_label')->nullable()->comment('Label for the box, e.g., "CFO Signature"');
            $table->timestamps();

            $table->foreign('amsm_id')
                ->references('id')
                ->on('ams_apprv_mstr')
                ->onDelete('cascade');

            $table->foreign('amsmd_id')
                ->references('id')
                ->on('ams_apprv_map_det')
                ->onDelete('cascade');

            $table->index(['amsm_id', 'amsmd_id']);
        });
    }

    public function down()
    {
        Schema::connection('sqlsrv_ams')->dropIfExists('ams_apprv_docsign_boxes');
    }
};
