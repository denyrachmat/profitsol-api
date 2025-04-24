<?php

namespace App\Traits\MRS;
use App\Models\MRS\MRSDBConnMstr;
use Illuminate\Support\Facades\Config;
use Doctrine\DBAL\DriverManager;
use Illuminate\Support\Facades\DB;

trait ConnectionDBTraits
{
    public function masterConn($id, $dbname, $manualConn = []) {

        if (!empty($id)) {
            $getConnTable = MRSDBConnMstr::where('id', $id)->first();
            $connectionParams = [
                'dbname' => $dbname,
                'user' => (string)$getConnTable->mdm_username,
                'password' => (string)$getConnTable->mdm_password,
                'host' => (string)$getConnTable->mdm_host,
                'driver' => 'sqlsrv',
            ];
        } else {
            $connectionParams = [
                'dbname' => $dbname,
                'user' => (string)$manualConn['username'],
                'password' => (string)$manualConn['password'],
                'host' => (string)$manualConn['host'],
                'driver' => 'sqlsrv',
            ];
        }

        $conn = DriverManager::getConnection($connectionParams);

        return $conn;
    }

    public function eloqConn($id, $dbname){
        try {
            $getConnTable = MRSDBConnMstr::where('id', $id)->first();

            DB::purge('sqlsrv_conn_dyn');
            Config::set("database.connections.sqlsrv_conn_dyn", [
                'driver' => 'sqlsrv',
                "host" => $getConnTable->mdm_host,
                "database" => $dbname,
                "username" => $getConnTable->mdm_username,
                "password" => $getConnTable->mdm_password
            ]);

            return true;
        } catch (\Throwable $th) {
            return $th;
            //throw $th;
        }
    }

    public function getCols($data) {
        $formatCols = [];
        foreach ($data as $key => $value) {
            $formatCols[] = [
                'cols' => [
                    'value' => $value['mrcd_field'],
                    'label' => $value['mrcd_label'],
                    'type' => $value['mrcd_fieldType']
                ],
                'opr' => '=',
                'conmet' => 'and',
                'name' => $value['mrcd_field'],
                'label' => $value['mrcd_label'],
                'sortable' => (bool)$value['mrcd_sortable'],
                'field' => $value['mrcd_field'],
                'active' => (bool)$value['mrcd_isActive'],
                'filterable' => (bool)$value['mrcd_isFiltered'],
                'exported' => (bool)$value['mrcd_isExported'],
                'type' => $value['mrcd_fieldType'],
                'value' => [''],
                'sortable_def' => (bool)$value['mrcd_sortable_def'],
            ];
        }

        return $formatCols;
    }

}
