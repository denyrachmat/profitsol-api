<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DmsCustomContentMappingApp extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_dms')->create('dms_custom_content_mapping_app', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('content_id');
            $table->string('app_flag')->nullable();
            $table->string('app_url')->nullable();
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
        Schema::connection('sqlsrv_dms')->dropIfExists('dms_custom_content_mapping_app');
    }
}
