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
use Doctrine\DBAL\Query\QueryBuilder;

use App\Exports\MRS\ExportReport;
use Illuminate\Support\Facades\Log;
use App\Traits\CMS\FormsTraits;

class ReportController extends BaseController
{
    use ConnectionDBTraits, FormsTraits;

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

    public function directStore(Request $request)
    {
        $id = isset($request->header['id']) ? $request->header['id'] : '';
        $insertHeader = MRSReportMstr::updateorcreate([
            'id' => $id
        ], $request->header);
        $insertCols = [];

        MRSReportColsDet::where('mrm_id', $insertHeader->id)
            ->delete();

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
                $data = DB::connection('sqlsrv_conn_dyn')->select('SET NOCOUNT ON;' . $request->code);

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

    // public function runningReport($idReport, Request $request)
    // {
    //     $cekReport = MRSReportMstr::select(
    //         'mrs_report_mstr.*',
    //         'mrs_db_mstr.mdm_host'
    //     )->join('mrs_db_mstr', 'mdm_id', 'mrs_db_mstr.id')
    //         ->where('mrs_report_mstr.id', $idReport)
    //         ->first();

    //     if (!empty($cekReport)) {
    //         $conn = $this->masterConn($cekReport->mdm_id, $cekReport->mrm_db);

    //         if ($cekReport->mrm_url_gen === 'sp') {
    //             $changeConn = $this->eloqConn($cekReport->mdm_id, $cekReport->mrm_db);
    //             if ($changeConn) {
    //                 $splitSPCode = explode(' ', $cekReport->mrm_query);

    //                 $finalCode = $splitSPCode[0] . ' ' . $splitSPCode[1];
    //                 if ($request->has('filter') && count($request->filter) > 0) {
    //                     foreach ($request->filter as $key => $valueFilter) {
    //                         $valuenya = $valueFilter['cols']['type'] == 'int'
    //                             ? $valueFilter['value'][0]
    //                             : "'" . $valueFilter['value'][0] . "'";

    //                         $finalCode .= ($key === 0 ? ' ' : ', ') . $valueFilter['cols']['value'] . '=' . $valuenya;
    //                     }
    //                 }
    //                 // return $finalCode;

    //                 $data = DB::connection('sqlsrv_conn_dyn')->select('SET NOCOUNT ON;' . $finalCode);

    //                 $data = json_decode(json_encode($data), true);

    //                 $parse = [
    //                     'data' => $data,
    //                     'page' => 1,
    //                     'rowsNumber' => count($data),
    //                     'query' => $finalCode
    //                 ];

    //                 if ($request->has('pagination')) {
    //                     return $this->handleResponse($parse, 'Data report found');
    //                 } else {
    //                     return $data;
    //                 }
    //             } else {
    //                 return $this->handleError('Connection change is failed !');
    //             }
    //         } else {
    //             if ($request->has('pagination')) {
    //                 $smBuild = $conn->createQueryBuilder()
    //                     ->select('RowConstrainedResult.*, ROW_NUMBER() OVER ( ORDER BY ' . $request->pagination['sortBy'] . ' ' . ($request->pagination['descending'] ? 'desc' : 'asc') . ') AS RowNum')
    //                     ->from('(' . $cekReport->mrm_query . ') AS RowConstrainedResult');
    //             } else {
    //                 $smBuild = $conn->createQueryBuilder()
    //                     ->select('RowConstrainedResult.*')
    //                     ->from('(' . $cekReport->mrm_query . ') AS RowConstrainedResult');
    //             }
    //             // Start filter state by users
    //             if ($request->has('filter') && count($request->filter) > 0) {
    //                 foreach ($request->filter as $key => $valueFilter) {
    //                     $checkExistsValue = array_filter($valueFilter['value'], fn($f) => $f !== null && $f !== '');

    //                     if (count($checkExistsValue) > 0) {
    //                         $valuenya1 = $valueFilter['cols']['type'] == 'int'
    //                             ? $valueFilter['value'][0]
    //                             : "'" . $valueFilter['value'][0] . "'";

    //                         $valuenya2 = isset($valueFilter['value'][1])
    //                             ? (
    //                                 $valueFilter['cols']['type'] == 'int'
    //                                 ? $valueFilter['value'][1]
    //                                 : "'" . $valueFilter['value'][1] . "'"
    //                             )
    //                             : null;

    //                         if ($valueFilter['opr'] == 'between') {
    //                             if ($valueFilter['conmet'] == 'and') {
    //                                 $smBuild->andwhere($valueFilter['cols']['value'] . ' between ' . $valuenya1 . ' and ' . $valuenya2);
    //                             } else {
    //                                 $smBuild->orwhere($valueFilter['cols']['value'] . ' between ' . $valuenya1 . ' and ' . $valuenya2);
    //                             }
    //                         } elseif ($valueFilter['opr'] == 'like') {
    //                             if ($valueFilter['conmet'] == 'and') {
    //                                 $smBuild->andwhere($valueFilter['cols']['value'] . " like '%" . $valueFilter['value'][0] . "%'");
    //                             } else {
    //                                 $smBuild->orwhere($valueFilter['cols']['value'] . " like '%" . $valueFilter['value'][0] . "%'");
    //                             }
    //                         } elseif ($valueFilter['opr'] == '<cols>') {
    //                             if ($valueFilter['conmet'] == 'and') {
    //                                 $smBuild->andwhere($valueFilter['cols']['value'] . " <> " . $valueFilter['value'][0]['value']);
    //                             } else {
    //                                 $smBuild->orwhere($valueFilter['cols']['value'] . " <> " . $valueFilter['value'][0]['value']);
    //                             }
    //                         } elseif ($valueFilter['opr'] == '=cols') {
    //                             if ($valueFilter['conmet'] == 'and') {
    //                                 $smBuild->andwhere($valueFilter['cols']['value'] . " = " . $valueFilter['value'][0]['value']);
    //                             } else {
    //                                 $smBuild->orwhere($valueFilter['cols']['value'] . " = " . $valueFilter['value'][0]['value']);
    //                             }
    //                         } else {
    //                             if ($valueFilter['conmet'] == 'and') {
    //                                 $smBuild->andwhere($valueFilter['cols']['value'] . " " . $valueFilter['opr'] . " " . $valuenya1);
    //                             } else {
    //                                 $smBuild->orwhere($valueFilter['cols']['value'] . " " . $valueFilter['opr'] . " " . $valuenya1);
    //                             }
    //                         }
    //                     }
    //                 }
    //             }
    //             // End filter state by users

    //             $getQuery = (clone $smBuild)->getSql();
    //             $smAllRecords = $conn->fetchAllAssociative('SELECT COUNT(*) as total FROM (' . $getQuery . ') a');

    //             $buildSelect = "";
    //             $listCols = MRSReportColsDet::where('mrm_id', $idReport)->where('mrcd_isActive', 1)->get();
    //             foreach ($listCols as $keyCols => $valueCols) {
    //                 $buildSelect .= $keyCols === 0 ? 'smb.' . $valueCols->mrcd_field : ', smb.' . $valueCols->mrcd_field;
    //             }

    //             $sm = $conn->createQueryBuilder()
    //                 ->select($buildSelect)
    //                 ->from('(' . (clone $smBuild)->getSql() . ') smb');

    //             if ($request->has('pagination')) {
    //                 if ($request->pagination['page'] == 1) {
    //                     $firstNum = 1;
    //                     $lastNum = $request->pagination['page'] * $request->pagination['rowsPerPage'];
    //                 } else {
    //                     $firstNum = $request->pagination['page'] * $request->pagination['rowsPerPage'];
    //                     $lastNum = $firstNum + $request->pagination['rowsPerPage'];

    //                     if ($lastNum >= $smAllRecords[0]['total']) {
    //                         $lastNum = $smAllRecords[0]['total'];
    //                     }
    //                 }
    //                 $sm->andwhere('smb.RowNum >= ' . ($firstNum))
    //                     ->andwhere('smb.RowNum <= ' . ($lastNum === 0 ? $smAllRecords[0]['total'] : $lastNum))
    //                     ->orderBy('smb.RowNum');

    //                 $hasil = [
    //                     'data' => $sm
    //                         ->executeQuery()
    //                         ->fetchAllAssociative(),
    //                     'page' => $request->pagination['page'],
    //                     'rowsNumber' => $smAllRecords[0]['total'],
    //                     'sortBy' => $request->pagination['sortBy'],
    //                     'query' => (clone $sm)->getSql(),
    //                     'rowsPerPage' => $request->pagination['rowsPerPage']
    //                 ];
    //             } else {
    //                 return $sm
    //                     ->executeQuery()
    //                     ->fetchAllAssociative();
    //             }
    //         }

    //         return $this->handleResponse($hasil, 'Data report found');
    //     } else {
    //         return $this->handleError('ID Report not found !');
    //     }
    // }

    public function runningReport($idReport, Request $request)
    {
        // 1. Get report configuration
        $cekReport = MRSReportMstr::with('database')
            ->where('id', $idReport)
            ->first();

        // return stripos($cekReport->mrm_url_gen, 'rpa');

        if (!$cekReport) {
            return $this->handleError('ID Report not found!');
        }

        // 2. Check if the report is generated by CMS URL
        if ($cekReport->mrm_url_gen == 'cms' || $cekReport->mrm_url_gen == 'rpa') {
            return $this->handleResponse($this->handleByCMSURL($cekReport, $request), 'Data report found');
        }

        // 2. Handle stored procedure case
        if ($cekReport->mrm_url_gen === 'sp') {
            return $this->handleResponse($this->handleStoredProcedureReport($cekReport, $request), 'Data report found');
        }

        // 3. Handle regular query report
        return $this->handleResponse($this->handleRegularReport($cekReport, $request), 'Data report found');
    }

    protected function handleStoredProcedureReport($report, $request)
    {
        $changeConn = $this->eloqConn($report->mdm_id, $report->mrm_db);
        if (!$changeConn) {
            return $this->handleError('Connection change failed!');
        }

        $spParts = explode(' ', $report->mrm_query, 2);
        $spName = $spParts[0];
        $spCommand = $spParts[1] ?? '';

        $parameters = [];
        if ($request->has('filter') && count($request->filter) > 0) {
            foreach ($request->filter as $filter) {
                if ($filter['value'][0] !== '') {

                    $value = is_numeric($filter['value'][0])
                        ? $filter['value'][0]
                        : "'" . str_replace("'", "''", $filter['value'][0]) . "'";
                    $parameters[] = "{$filter['cols']['value']}={$value}";
                }
            }
        }

        $execStatement = "{$spName} {$spCommand} " . implode(', ', $parameters);

        try {
            $data = DB::connection('sqlsrv_conn_dyn')
                ->select("SET NOCOUNT ON; {$execStatement}");

            $result = [
                'data' => json_decode(json_encode($data), true),
                'page' => 1,
                'rowsNumber' => count($data),
                'query' => $execStatement
            ];

            return $request->has('pagination')
                ? $this->handleResponse($result, 'Data report found')
                : $data;
        } catch (\Exception $e) {
            return $this->handleError('SP Execution failed: ' . $e->getMessage());
        }
    }

    protected function handleByCMSURL($report, $request)
    {
        $filter = [];
        if ($request->has('filter') && count($request->filter) > 0) {
            foreach ($request->filter as $filterItem) {
                if (!empty($filterItem['cols'])) {
                    $filter[] = [
                        'column' => $filterItem['cols']['value'],
                        'operator' => $filterItem['opr'],
                        'value' => $filterItem['value'][0] ?? null,
                    ];
                }
            }
        }

        $getData = $this->showHistory(new Request(['filter' => $filter, 'pagination' => $request->pagination]), $report->mrm_query);

        // return $getData;
        // // Decode the response content to access data
        $responseData = json_decode($getData->getContent(), true);
        if ($responseData['status'] === false) {
            return $this->handleError('CMS URL report generation failed: ' . $responseData['message']);
        }

        // Assuming the response contains 'data' key with the report data
        $data = $responseData['data'] ?? [];

        return $data;

        // return $this->handleError('CMS URL report generation not implemented yet.');
    }

    protected function sanitizeBaseQuery(string $query): string
    {
        // Add any necessary query sanitization here
        return $query;
    }

    protected function quoteIdentifier(string $identifier): string
    {
        // Properly quote identifiers for SQL Server
        return '[' . str_replace(']', ']]', $identifier) . ']';
    }

    protected function getTotalCount($conn, $baseQuery, $filters): int
    {
        try {
            // 1. First try with explicit type casting in SQL
            $countQuery = $conn->createQueryBuilder()
                ->select('COUNT_BIG(*) AS total') // Using COUNT_BIG for large tables
                ->from('(' . $baseQuery . ')', 'base_query');

            $this->applyFilters($countQuery, $filters);

            $result = $countQuery->executeQuery()->fetchAssociative();
            return (int) ($result['total'] ?? 0);

        } catch (\Exception $e) {
            // 2. Fallback to simple count with error handling
            try {
                $simpleCount = $conn->createQueryBuilder()
                    ->select('COUNT(*) AS total')
                    ->from('(' . $baseQuery . ')', 'base_query')
                    ->executeQuery()
                    ->fetchOne();

                return (int) $simpleCount;

            } catch (\Exception $e) {
                // 3. Ultimate fallback with raw query
                $rawCount = $conn->executeQuery(
                    "SELECT COUNT(*) FROM ({$baseQuery}) AS base_query"
                )->fetchOne();

                return (int) $rawCount;
            }
        }
    }

    protected function handleRegularReport($report, $request)
    {
        $conn = $this->masterConn($report->mdm_id, $report->mrm_db);

        try {
            // 1. Build base query with filters
            $baseQuery = $conn->createQueryBuilder()
                ->from('(' . $report->mrm_query . ')', 'base_query');

            $this->applyFilters($baseQuery, $request->filter ?? []);

            // 2. Get total count with robust handling
            $totalCount = $this->getSafeCount($conn, $report->mrm_query, $request->filter ?? []);

            // 3. Get active columns
            $columns = MRSReportColsDet::where('mrm_id', $report->id)
                ->where('mrcd_isActive', 1)
                ->pluck('mrcd_field')
                ->map(fn($field) => "base_query." . $this->quoteIdentifier($field))
                ->implode(', ');

            // 4. Apply sorting and pagination
            if ($request->has('pagination')) {
                $baseQuery
                    ->select($columns)
                    ->orderBy(
                        $this->quoteIdentifier($request->pagination['sortBy']),
                        $request->pagination['descending'] ? 'DESC' : 'ASC'
                    )
                    ->setFirstResult(($request->pagination['page'] - 1) * $request->pagination['rowsPerPage'])
                    ->setMaxResults($request->pagination['rowsPerPage']);
            } else {
                $baseQuery->select($columns);
            }

            // 5. Execute and return
            $data = $baseQuery->executeQuery()->fetchAllAssociative();

            if ($request->has('pagination')) {
                $result = [
                    'data' => $data,
                    'page' => $request->pagination['page'],
                    'rowsNumber' => $totalCount,
                    'sortBy' => $request->pagination['sortBy'],
                    'rowsPerPage' => $request->pagination['rowsPerPage']
                ];
                return $this->handleResponse($result, 'Data report found');
            }

            return $data;

        } catch (\Exception $e) {
            Log::error('Report generation failed', [
                'error' => $e->getMessage(),
                'query' => $report->mrm_query,
                'request' => $request->all()
            ]);
            return $this->handleError('Report generation failed: ' . $e->getMessage());
        }
    }

    protected function getSafeCount($conn, $baseQuery, $filters): int
    {
        try {
            // First try with COUNT_BIG and explicit type handling
            $countQuery = $conn->createQueryBuilder()
                ->select('COUNT_BIG(*) AS total_count')
                ->from('(' . $baseQuery . ')', 'base_query');

            $this->applyFilters($countQuery, $filters);

            $result = $countQuery->executeQuery()->fetchAssociative();
            return (int) ($result['total_count'] ?? 0);

        } catch (\Exception $e) {
            Log::warning('Standard count failed, trying simple count', ['error' => $e->getMessage()]);

            // Fallback to simple count without filters
            try {
                $simpleCount = $conn->executeQuery(
                    "SELECT COUNT(*) FROM ({$baseQuery}) AS base_query"
                )->fetchOne();

                return (int) $simpleCount;

            } catch (\Exception $e) {
                Log::error('All count methods failed', ['error' => $e->getMessage()]);
                return 0; // Final fallback
            }
        }
    }

    protected function applyFilters(QueryBuilder $query, array $filters): void
    {
        foreach ($filters as $filter) {
            $values = array_filter($filter['value'] ?? [], fn($v) => $v !== null && $v !== '');
            if (empty($values))
                continue;

            $whereMethod = $filter['conmet'] === 'and' ? 'andWhere' : 'orWhere';
            $column = $this->quoteIdentifier($filter['cols']['value']);
            $isNumeric = $filter['cols']['type'] === 'int';

            // Special case for column-to-column comparison
            if ($filter['opr'] === '<cols>') {
                $otherColumn = $this->quoteIdentifier($values[0]['value']);
                $query->$whereMethod("{$column} <> {$otherColumn}");
                continue;
            }

            // Handle regular value comparisons
            $paramPrefix = 'filter_' . uniqid();

            switch ($filter['opr']) {
                case 'between':
                    $val1 = $isNumeric ? (float) $values[0] : $values[0];
                    $val2 = $isNumeric ? (float) ($values[1] ?? $values[0]) : ($values[1] ?? $values[0]);

                    $query->$whereMethod("{$column} BETWEEN :{$paramPrefix}_1 AND :{$paramPrefix}_2")
                        ->setParameter("{$paramPrefix}_1", $val1, $isNumeric ? \PDO::PARAM_INT : \PDO::PARAM_STR)
                        ->setParameter("{$paramPrefix}_2", $val2, $isNumeric ? \PDO::PARAM_INT : \PDO::PARAM_STR);
                    break;

                case 'like':
                    $query->$whereMethod("{$column} LIKE :{$paramPrefix}")
                        ->setParameter($paramPrefix, '%' . $values[0] . '%', \PDO::PARAM_STR);
                    break;

                case '>':
                case '<':
                case '=':
                case '>=':
                case '<=':
                case '<>':
                    $value = is_array($values[0]) ? ($values[0]['value'] ?? null) : $values[0];
                    $query->$whereMethod("{$column} {$filter['opr']} :{$paramPrefix}")
                        ->setParameter($paramPrefix, $value, $isNumeric ? \PDO::PARAM_INT : \PDO::PARAM_STR);
                    break;

                default:
                    Log::warning('Unsupported operator', ['operator' => $filter['opr']]);
            }
        }
    }

    public function exportToExcel($idReport, Request $request)
    {
        $cekReport = MRSReportMstr::select(
            'mrs_report_mstr.*'
        )
            ->where('mrs_report_mstr.id', $idReport)
            ->first();

        $getData = $this->runningReport($idReport, $request);

        // Convert stdClass to array if needed
        if (is_object($getData)) {
            $getData = json_decode(json_encode($getData), true);
        }

        // return $getData;

        $filename = 'export_' . $cekReport->mrm_name . '_' . date('ymd_his') . '.xlsx';
        // return $getData;
        Excel::store(new ExportReport($getData, $idReport), 'MRS/' . $filename, 'public');

        return [
            'status' => true,
            'path' => 'storage/MRS/' . $filename,
        ];
    }
}
