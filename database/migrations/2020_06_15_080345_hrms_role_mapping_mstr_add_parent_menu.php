<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HrmsRoleMappingMstrAddParentMenu extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv_hrms')->table('hrms_role_mapping_mstr', function (Blueprint $table) {
            $table->string('parent_menu_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('sqlsrv_hrms')->table('hrms_role_mapping_mstr', function (Blueprint $table) {
            $table->dropColumn(['parent_menu_id']);
        });
    }
}
