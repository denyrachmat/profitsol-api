<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCmsFormMstrTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_cms')->create('cms_form_mstr', function (Blueprint $table) {
            $table->id();
            $table->string('p_u_username');
            $table->integer('cfmt_id');
            $table->string('cfm_type');
            $table->integer('cfm_required')->default(0);
            $table->string('cfm_seq_name')->nullable();
            $table->string('cfm_content')->nullable();
            $table->integer('cfm_parent_id')->nullable();
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
        Schema::connection('sqlsrv_cms')->dropIfExists('cms_form_mstr');
    }
}
