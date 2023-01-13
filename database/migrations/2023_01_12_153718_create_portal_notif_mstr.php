<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePortalNotifMstr extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('portal_notif_mstr', function (Blueprint $table) {
            $table->id();
            $table->string('p_u_username');
            $table->string('pnm_to_users');
            $table->string('pnm_title');
            $table->text('pnm_content');
            $table->string('pnm_action_url');
            $table->string('pnm_hash_id_location')->nullable();
            $table->datetime('pnm_start_date')->nullable();
            $table->datetime('pnm_end_date')->nullable();
            $table->boolean('pnm_is_read')->default(0);
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
        Schema::dropIfExists('portal_notif_mstr');
    }
}
