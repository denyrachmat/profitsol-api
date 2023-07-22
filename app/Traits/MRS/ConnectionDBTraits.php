<?php

namespace App\Traits\MRS;
use App\Models\MRS\MRSDBConnMstr;
use Doctrine\DBAL\DriverManager;

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

    public function getCols($data) {
        $formatCols = [];
        foreach ($data as $key => $value) {
            $formatCols[] = [
                'name' => $value['mrcd_field'],
                'label' => $value['mrcd_label'],
                'sortable' => $value['mrcd_sortable'],
                'field' => $value['mrcd_field'],
                'active' => $value['mrcd_isActive'],
                'filterable' => $value['mrcd_sortable']
            ];
        }

        return $formatCols;
    }

}