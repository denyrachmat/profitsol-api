<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePortalUsersStudyDetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('portal_users_study_det', function (Blueprint $table) {
            $table->id();
            $table->string('u_username');
            $table->string('pufd_id')->nullable();
            $table->string('pusd_level');
            $table->string('pusd_sch_name');
            $table->string('pusd_sch_majors')->nullable();
            $table->string('pusd_sch_minors')->nullable();
            $table->string('pusd_sch_addr')->nullable();
            $table->date('pusd_sch_start')->nullable();
            $table->date('pusd_sch_end')->nullable();
            $table->string('pusd_grade')->nullable();
            $table->boolean('pusd_sch_passed');
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
        Schema::dropIfExists('portal_users_study_det');
    }
}
