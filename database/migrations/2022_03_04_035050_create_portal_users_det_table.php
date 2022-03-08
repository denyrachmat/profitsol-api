<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePortalUsersDetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('portal_users_det', function (Blueprint $table) {
            $table->id();
            $table->string('u_username')->unique();
            $table->boolean('pud_is_verified')->default(0);
            $table->boolean('pud_is_active')->default(0);
            $table->string('pud_first_name');
            $table->string('pud_last_name');
            $table->string('pud_id_card');
            $table->string('pud_photo');
            $table->string('pud_country')->nullable();
            $table->string('pud_states')->nullable();
            $table->string('pud_district')->nullable();
            $table->string('pud_subdistrict')->nullable();
            $table->text('pud_addr1')->nullable();
            $table->text('pud_addr2')->nullable();
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
        Schema::dropIfExists('portal_users_det');
    }
}
