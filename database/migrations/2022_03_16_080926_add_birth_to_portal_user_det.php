<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBirthToPortalUserDet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('portal_users_det', function (Blueprint $table) {
            $table->string('pud_id_type')->nullable();
            $table->string('pud_birth_place')->nullable();
            $table->date('pud_birth_date')->nullable();
            $table->string('pud_country_rsdn')->nullable();
            $table->string('pud_district_rsdn')->nullable();
            $table->string('pud_subdistrict_rsdn')->nullable();
            $table->text('pud_addr1_rsdn')->nullable();
            $table->text('pud_addr2_rsdn')->nullable();
        });
    }   

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('portal_user_det', function (Blueprint $table) {
            //
        });
    }
}
