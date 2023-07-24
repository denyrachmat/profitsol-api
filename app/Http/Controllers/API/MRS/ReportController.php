<?php

namespace App\Http\Controllers\API\MRS;

use App\Http\Controllers\API\PORTAL\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\MRS\MRSDBConnMstr;
use App\Models\MRS\MRSReportMstr;
use App\Models\MRS\MSReportColsDet;

use App\Http\Requests\MRS\ReportCreateRequest;

use App\Traits\MRS\ConnectionDBTraits;

class ReportController extends BaseController
{
    use ConnectionDBTraits;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = MRSReportMstr::select(
                'mrs_report_mstr.*',
                'mrs_db_mstr.mdm_host'
            )->join('mrs_db_mstr', 'mdm_id', 'mrs_db_mstr.id')
            ->get()
            ->toArray();

        $hasil = [];
        foreach ($data as $key => $value) {
            $hasil[] = array_merge($value, [
                'cols' => $this->getCols(MSReportColsDet::where('mrm_id', $value['id'])->get()->toArray())
            ]);
        }

        return $this->handleResponse($hasil, 'Data Found');
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
    public function store(ReportCreateRequest $request)
    {
        $insertHeader = MRSReportMstr::updateorcreate([
            'id' => $request->header['id']
        ],$request->header);
        $insertCols = [];

        foreach ($request->det as $key => $value) {
            $insertCols[] = MSReportColsDet::updateorcreate([
                'mrm_id' => $insertHeader->id,
                'mrcd_field' => $value['field']
            ],[
                'mrm_id' => $insertHeader->id,
                'mrcd_field' => $value['field'],
                'mrcd_label' => $value['label'],
                'mrcd_isActive' => $value['active'],
                'mrcd_sortable' => $value['sortable'],
                'mrcd_isFiltered' => $value['filterable'],
                'mrcd_isExported' => $value['exported'],
            ]);
        }
        return $this->handleResponse(['header' => $insertHeader, 'det' => $insertCols], 'Data Created !');
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

    public function simRunning(Request $request)
    {
        $conn = $this->masterConn($request->id, $request->dbname);

        $limitingQuery = str_replace('SELECT', 'SELECT TOP(20)', $request->code);
        $sm = $conn->fetchAllAssociative($limitingQuery);

        $getCols = $conn->fetchAllAssociative("SELECT name FROM sys.dm_exec_describe_first_result_set ('$limitingQuery', NULL, 0);");

        $formatCols = [];
        foreach ($getCols as $key => $value) {
            $formatCols[] = [
                'name' => $value['name'],
                'label' => $value['name'],
                'sortable' => true,
                'field' => $value['name'],
                'active' => true,
                'filterable' => true
            ];
        }

        return [
            'cols' => $formatCols,
            'data' => $sm,
            'status' => true
        ];
    }

    public function runningReport($idReport, Request $request)
    {
        $cekReport = MRSReportMstr::select(
            'mrs_report_mstr.*',
            'mrs_db_mstr.mdm_host'
        )->join('mrs_db_mstr', 'mdm_id', 'mrs_db_mstr.id')
            ->where('mrs_report_mstr.id', $idReport)
            ->first();

        if (!empty($cekReport)) {
            $conn = $this->masterConn($cekReport->mdm_id, $cekReport->mrm_db);

            $limitingQuery = str_replace('SELECT', 'SELECT ROW_NUMBER() OVER ( ORDER BY ' . $request->pagination['sortBy'] . ' ) AS RowNum,', $cekReport->mrm_query);

            $sm = $conn->createQueryBuilder()
                ->select('*')
                ->from('(' . $limitingQuery . ') AS RowConstrainedResult')
                ->where('RowNum >= ?')
                ->setParameter(0, $request->pagination['page'] == 1 ? 1 : $request->pagination['page'] * $request->pagination['rowsPerPage'])
                ->where('RowNum < ?')
                ->setParameter(0, ($request->pagination['page'] * $request->pagination['rowsPerPage']) + $request->pagination['rowsPerPage']);

            $smAllRecords = $conn->fetchAllAssociative('SELECT COUNT(*) as total FROM ('.$limitingQuery.') a');

            $hasil = [
                'data' => $sm
                    ->orderBy('RowNum')
                    ->executeQuery()
                    ->fetchAllAssociative(),
                'page' => $request->pagination['page'],
                'rowsNumber' => $smAllRecords[0]['total'],
                'sortBy' => $request->pagination['sortBy'],
            ];

            return $this->handleResponse($hasil, 'Data report found');
        } else {
            return $this->handleError('ID Report not found !');
        }
    }
}