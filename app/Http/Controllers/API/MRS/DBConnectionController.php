<?php

namespace App\Http\Controllers\API\MRS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\API\PORTAL\BaseController as BaseController;
use Illuminate\Support\Facades\Config;
use Doctrine\DBAL\DriverManager;

use App\Models\MRS\MRSDBConnMstr;
use App\Http\Requests\MRS\DBConnectionCreateRequest;
use Illuminate\Support\Facades\DB;

use App\Traits\MRS\ConnectionDBTraits;

class DBConnectionController extends BaseController
{
    use ConnectionDBTraits;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = MRSDBConnMstr::select(
            'id as value',
            DB::raw("concat(mdm_name, ' ( ', mdm_host,' )') as label")
        )->get();
        return $this->handleResponse($data, 'Data Found !');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(DBConnectionCreateRequest $request)
    {
        $insert = MRSDBConnMstr::create($request->all());
        return $this->handleResponse($insert, 'Data Created !');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function listDB($id, $list = 'db', $dbname = 'master'){
        $conn = $this->masterConn($id, $dbname);
        $sm = $conn->createSchemaManager();

        if ($list == 'db') {
            return $this->handleResponse($sm->listDatabases(), 'Data Found !');
        } elseif ($list == 'view') {
            $tables = $sm->listViews();

            $listTable = [];
            foreach ($tables as $table) {
                $listTable[] = $table->getName();
            }
            
            sort($listTable);
            return $this->handleResponse($listTable, 'Data Found !');
        } elseif ($list == 'sp') {
            return $this->handleResponse($sm->listSequences($dbname), 'Data Found !');
        } elseif ($list == 'tables') {
            $tables = $sm->listTables();

            $listTable = [];
            foreach ($tables as $table) {
                $listTable[] = $table->getName();
            }

            sort($listTable);
            return $this->handleResponse($listTable, 'Data Found !');
        } else {
            return $this->handleError('No list defined');
        }
    }

    public function testConnection(Request $request){
        $data = $request->all();
    
        $conn = $this->masterConn('', 'master', $data);
        return $this->handleResponse($conn->connect(), 'Connection DB Secured !');
    }
}
