<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDmsDocFolderUserShareMap extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_dms')->create('dms_doc_folder_user_share_map', function (Blueprint $table) {
            $table->id();
            $table->string('p_u_username');
            $table->integer('ddm_id')->nullable();
            $table->integer('dfm_id')->nullable();
            $table->string('ddfus_p_u_username');
            $table->boolean('ddfus_read')->default(0);
            $table->boolean('ddfus_write')->default(0);
            $table->string('ddfus_token')->nullable(); // Added this line
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
        Schema::dropIfExists('dms_doc_folder_user_share_map');
    }
}
