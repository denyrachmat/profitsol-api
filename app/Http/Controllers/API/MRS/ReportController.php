<?php

namespace App\Http\Controllers\API\MRS;

use App\Http\Controllers\API\PORTAL\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\MRS\MRSDBConnMstr;
use App\Models\MRS\MRSReportMstr;
use App\Models\MRS\MRSReportColsDet;

use App\Http\Requests\MRS\ReportCreateRequest;

use App\Traits\MRS\ConnectionDBTraits;
use Excel;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Tests\NewRequest;

use App\Exports\MRS\ExportReport;

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
                'cols' => $this->getCols(MRSReportColsDet::where('mrm_id', $value['id'])->where('mrcd_col_prop', 'cols')->get()->toArray()),
                'colsParam' => $this->getCols(MRSReportColsDet::where('mrm_id', $value['id'])->where('mrcd_col_prop', 'params')->get()->toArray())
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
        ], $request->header);
        $insertCols = [];

        foreach ($request->det as $key => $value) {
            $insertCols[] = MRSReportColsDet::updateorcreate([
                'mrm_id' => $insertHeader->id,
                'mrcd_field' => $value['field']
            ], [
                'mrm_id' => $insertHeader->id,
                'mrcd_field' => $value['field'],
                'mrcd_label' => $value['label'],
                'mrcd_isActive' => $value['active'],
                'mrcd_sortable' => $value['sortable'],
                'mrcd_isFiltered' => $value['filterable'],
                'mrcd_isExported' => $value['exported'],
                'mrcd_fieldType' => $value['type'],
                'mrcd_col_prop' => 'cols'
            ]);
        }

        $insertCols2 = [];
        if (isset($request->detParams) && count($request->detParams) > 0) {
            foreach ($request->detParams as $key => $valueParams) {
                $insertCols2[] = MRSReportColsDet::updateorcreate([
                    'mrm_id' => $insertHeader->id,
                    'mrcd_field' => $valueParams['field']
                ], [
                    'mrm_id' => $insertHeader->id,
                    'mrcd_field' => $valueParams['field'],
                    'mrcd_label' => $valueParams['label'],
                    'mrcd_isActive' => $valueParams['active'],
                    'mrcd_sortable' => $valueParams['sortable'],
                    'mrcd_isFiltered' => $valueParams['filterable'],
                    'mrcd_isExported' => true,
                    'mrcd_fieldType' => $valueParams['type'],
                    'mrcd_col_prop' => 'params'
                ]);
            }
        }

        return $this->handleResponse(['header' => $insertHeader, 'det' => $insertCols, 'detParams' => $insertCols2], 'Data Created !');
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
        $master = MRSReportMstr::where('id', $id)->delete();
        $colDet = MRSReportColsDet::where('mrm_id', $id)->delete();

        return $this->handleResponse(['header' => $master, 'det' => $colDet], 'Data Deleted !');
    }

    public function simRunning(Request $request)
    {
        $conn = $this->masterConn($request->id, $request->dbname);
        
        if ($request->type === 'sp') {
            // $data = $conn->fetchAllAssociative($request->code);

            $changeConn = $this->eloqConn($request->id, $request->dbname);
            if ($changeConn) {
                $data = DB::connection('sqlsrv_conn_dyn')->select('SET NOCOUNT ON;'.$request->code);
                
                $data = json_decode(json_encode($data), true);
                $cols = [];
                foreach ($data as $key => $value) {
                    if ($key == 0) {
                        $colsConv = array_keys($value);
                        foreach ($colsConv as $keyCol => $valueCol) {
                            $cols[] = [
                                'name' => $valueCol,
                                'label' => $valueCol,
                                'sortable' => true,
                                'field' => $valueCol,
                                'active' => true,
                                'filterable' => true,
                                'exported' => true
                            ];
                        }
                    }
                }

                $param = [];
                foreach ($request->param as $keyParam => $valueParam) {
                    $param[] = [
                        'name' => $valueParam,
                        'label' => $valueParam,
                        'sortable' => true,
                        'field' => $valueParam,
                        'active' => true,
                        'filterable' => true,
                        'exported' => true
                    ];
                }

                if (count($data) === 0) {
                    return [
                        'cols' => $cols,
                        'data' => $data,
                        'params' => $param,
                        'status' => false,
                        'message' => 'Data store procedure empty !'
                    ];
                }

                return [
                    'cols' => $cols,
                    'data' => $data,
                    'params' => $param,
                    'status' => true
                ];
            } else {
                return [
                    'cols' => [],
                    'data' => [],
                    'status' => false
                ];
            }
        } else {
            $limitingQuery = str_replace('SELECT', 'SELECT TOP(20)', $request->code);
            $sm = $conn->fetchAllAssociative($limitingQuery);

            $getCols = array_keys($sm[0]);

            $formatCols = [];
            foreach ($getCols as $key => $value) {
                $formatCols[] = [
                    'name' => $value,
                    'label' => $value,
                    'sortable' => true,
                    'field' => $value,
                    'active' => true,
                    'filterable' => true,
                    'exported' => true
                ];
            }
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
            
            if ($cekReport->mrm_url_gen === 'sp') {
                $changeConn = $this->eloqConn($cekReport->mdm_id, $cekReport->mrm_db);
                if ($changeConn) {
                    $splitSPCode = explode(' ', $cekReport->mrm_query);

                    // return $splitSPCode;
                    $finalCode = $splitSPCode[0].' '.$splitSPCode[1];
                    if ($request->has('filter') && count($request->filter) > 0) {
                        foreach ($request->filter as $key => $valueFilter) {
                            if (!empty($valueFilter['value'][0])) {
                                $valuenya = $valueFilter['cols']['type'] == 'int'
                                ? $valueFilter['value'][0]
                                : "'" . $valueFilter['value'][0] . "'";
    
                                $finalCode .= ($key === 0 ? ' ' : ', ') .$valueFilter['cols']['value'].'='.$valuenya;
                            }
                        }
                    }

                    $data = DB::connection('sqlsrv_conn_dyn')->select('SET NOCOUNT ON;'.$finalCode);
                    
                    $data = json_decode(json_encode($data), true);

                    $parse = [
                        'data' => $data,
                        'page' => 1,
                        'rowsNumber' => count($data),
                        'query' => $finalCode
                    ];

                    if ($request->has('pagination')) {
                        return $this->handleResponse($parse, 'Data report found');
                    } else {
                        return $data;
                    }
                } else {
                    return $this->handleError('Connection change is failed !');
                }
            } else {
                if ($request->has('pagination')) {
                    $smBuild = $conn->createQueryBuilder()
                        ->select('RowConstrainedResult.*, ROW_NUMBER() OVER ( ORDER BY ' . $request->pagination['sortBy'] . ' ' . ($request->pagination['descending'] ? 'desc' : 'asc') . ') AS RowNum')
                        ->from('(' . $cekReport->mrm_query . ') AS RowConstrainedResult');
                } else {
                    $smBuild = $conn->createQueryBuilder()
                        ->select('RowConstrainedResult.*')
                        ->from('(' . $cekReport->mrm_query . ') AS RowConstrainedResult');
                }
                // Start filter state by users
                if ($request->has('filter') && count($request->filter) > 0) {
                    foreach ($request->filter as $key => $valueFilter) {
                        $valuenya1 = $valueFilter['cols']['type'] == 'int'
                            ? $valueFilter['value'][0]
                            : "'" . $valueFilter['value'][0] . "'";
    
                        $valuenya2 = isset($valueFilter['value'][1])
                            ? (
                                $valueFilter['cols']['type'] == 'int'
                                ? $valueFilter['value'][1]
                                : "'" . $valueFilter['value'][1] . "'"
                            )
                            : null;
    
                        if ($valueFilter['opr'] == 'between') {
                            if ($valueFilter['conmet'] == 'and') {
                                $smBuild->andwhere($valueFilter['cols']['value'] . ' between ' . $valuenya1 . ' and ' . $valuenya2);
                            } else {
                                $smBuild->orwhere($valueFilter['cols']['value'] . ' between ' . $valuenya1 . ' and ' . $valuenya2);
                            }
                        } elseif ($valueFilter['opr'] == 'like') {
                            if ($valueFilter['conmet'] == 'and') {
                                $smBuild->andwhere($valueFilter['cols']['value'] . " like '%" . $valueFilter['value'][0] . "%'");
                            } else {
                                $smBuild->orwhere($valueFilter['cols']['value'] . " like '%" . $valueFilter['value'][0] . "%'");
                            }
                        } else {
                            if ($valueFilter['conmet'] == 'and') {
                                $smBuild->andwhere($valueFilter['cols']['value'] . " " . $valueFilter['opr'] . " " . $valuenya1);
                            } else {
                                $smBuild->orwhere($valueFilter['cols']['value'] . " " . $valueFilter['opr'] . " " . $valuenya1);
                            }
                        }
                    }
                }
                // End filter state by users

                $getQuery = (clone $smBuild)->getSql();
                $smAllRecords = $conn->fetchAllAssociative('SELECT COUNT(*) as total FROM (' . $getQuery . ') a');
                
                $buildSelect = "";
                $listCols = MRSReportColsDet::where('mrm_id',$idReport)->where('mrcd_isActive', 1)->get();
                foreach ($listCols as $keyCols => $valueCols) {
                    $buildSelect .= $keyCols === 0 ? 'smb.'.$valueCols->mrcd_field : ', smb.'.$valueCols->mrcd_field;
                }

                $sm = $conn->createQueryBuilder()
                    ->select($buildSelect)
                    ->from('(' . (clone $smBuild)->getSql() . ') smb');
    
                if ($request->has('pagination')) {
                    if ($request->pagination['page'] == 1) {
                        $firstNum = 1;
                        $lastNum = $request->pagination['page'] * $request->pagination['rowsPerPage'];
                    } else {
                        $firstNum = $request->pagination['page'] * $request->pagination['rowsPerPage'];
                        $lastNum = $firstNum + $request->pagination['rowsPerPage'];

                        if ($lastNum >= $smAllRecords[0]['total']) {
                            $lastNum = $smAllRecords[0]['total'];
                        }
                    }
                    $sm->andwhere('smb.RowNum >= ' . ($firstNum))
                        ->andwhere('smb.RowNum <= ' . ($lastNum))
                        ->orderBy('smb.RowNum');
    
                    $hasil = [
                        'data' => $sm
                            ->executeQuery()
                            ->fetchAllAssociative(),
                        'page' => $request->pagination['page'],
                        'rowsNumber' => $smAllRecords[0]['total'],
                        'sortBy' => $request->pagination['sortBy'],
                        'query' => (clone $sm)->getSql(),
                        'rowsPerPage' => $request->pagination['rowsPerPage']
                    ];
                } else {
                    return $sm
                        ->executeQuery()
                        ->fetchAllAssociative();
                }
            }

            return $this->handleResponse($hasil, 'Data report found');
        } else {
            return $this->handleError('ID Report not found !');
        }
    }

    public function exportToExcel($idReport, Request $request)
    {
        $getData = $this->runningReport($idReport, $request);

        // return $getData;
        Excel::store(new ExportReport($getData, $idReport), 'MRS/export_report.xlsx', 'public');

        return [
            'status' => true,
            'path' => 'storage/app/public/MRS/export_report.xlsx'
        ];
    }
}