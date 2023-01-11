<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCmsFormSetupDetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_cms')->create('cms_form_setup_det', function (Blueprint $table) {
            $table->id();
            $table->integer('cfmt_id');
            $table->boolean('cfsd_res_show')->default(0);
            $table->boolean('cfsd_ans_show')->default(0);
            $table->boolean('cfsd_rand_quest')->default(0);
            $table->string('cfsd_ans_loc')->default('end');
            $table->boolean('cfsd_timer')->default(0);
            $table->boolean('cfsd_timer_quest')->default(0);
            $table->decimal('cfsd_hours')->default(0);
            $table->decimal('cfsd_min')->default(0);
            $table->decimal('cfsd_sec')->default(0);
            $table->decimal('cfsd_min_pass')->default(100);
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
        Schema::connection('sqlsrv_cms')->dropIfExists('cms_form_setup_det');
    }
}
