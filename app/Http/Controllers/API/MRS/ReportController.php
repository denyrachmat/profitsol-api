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
use App\Traits\PORTAL\GencodeTraits;

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

    public function runningReportFromAPI($token)
    {
        $getData = $this->getDataGencode(
            'MRS_API_GEN',
            [
                'pgm_value' => $token,
            ],
            [
                'filter' => 'pgm_value2|array',
                'id_report' => 'pgm_value3|int',
            ],
            [],
            true
        );

        $data = $this->runningReport($getData['id_report'], new Request([
            'filter' => json_decode($getData['filter'], true)
        ]))->getOriginalContent();

        $getCols = MRSReportColsDet::where('mrm_id', $getData['id_report'])
            ->where('mrcd_isActive', 1)
            ->where('mrcd_isExported', 1)
            ->where('mrcd_col_prop', 'cols')
            ->get()
            ->toArray();

        $result = [];
        foreach ($data['data'] as $key => $value) {
            foreach ($getCols as $keyCols => $valueCols) {
                if (in_array($valueCols['mrcd_field'], array_keys($value))) {
                    $result[$key][strtolower(str_replace(' ', '_', $valueCols['mrcd_label']))] = $value[$valueCols['mrcd_field']];
                }
            }
             // Assuming at least one column exists
        }

        return $result;
    }

    public function runningReport($idReport, Request $request)
    {
        // 1. Get report configuration
        $cekReport = MRSReportMstr::with('database')
            ->where('id', $idReport)
            ->first();

        if (!$cekReport) {
            return $this->handleError('ID Report not found!');
        }

        // 2. Check if the report is generated by CMS URL
        if (stripos($cekReport->mrm_url_gen, 'cms') !== false || stripos($cekReport->mrm_url_gen, 'rpa') !== false) {
            $dataCMS = $this->handleByCMSURL($cekReport, $request);
            return $this->handleResponse($dataCMS, 'Data report found');
        }

        // 2. Handle stored procedure case
        if ($cekReport->mrm_url_gen == 'sp') {
            $dataSP = $this->handleStoredProcedureReport($cekReport, $request);
            if (!$request->has('pagination')) {
                return $this->handleResponse($dataSP, 'Data report found');
            }

            if (is_array($dataSP) && $dataSP['status']) {
                return $this->handleResponse($dataSP, 'Data report found');
            }

            return is_array($dataSP) ? $this->handleError($dataSP['message']) : $dataSP;
        }

        // 3. Handle regular query report
        $result = $this->handleRegularReport($cekReport, $request)->getOriginalContent();

        if ($result['status']) {
            return $this->handleResponse($result['data'], 'Data report found');
        }

        return $this->handleError($result['message']);
    }

    protected function handleStoredProcedureReport($report, $request)
    {
        $changeConn = $this->eloqConn($report->mdm_id, $report->mrm_db);
        if (!$changeConn) {
            return ['status' => false, 'message' => 'Connection change failed!'];
        }

        // Remove 'EXEC' prefix if it already exists in the query
        $query = trim($report->mrm_query);
        if (stripos($query, 'EXEC') === 0) {
            $query = substr($query, 4);
        }

        $spParts = explode(' ', trim($query), 2);
        $spName = trim($spParts[0]);
        $spCommand = isset($spParts[1]) ? trim($spParts[1]) : '';

        $parameters = [];
        $addedParams = [];
        if ($request->has('filter') && count($request->filter) > 0) {
            foreach ($request->filter as $filter) {
                $paramName = $filter['field'];
                if (in_array($paramName, $addedParams)) {
                    continue;
                }

                $filterValues = $filter['value'] ?? [];
                if (is_array($filterValues) && !empty($filterValues[0])) {
                    $value = is_numeric($filterValues[0])
                        ? $filterValues[0]
                        : "'" . str_replace("'", "''", $filterValues[0]) . "'";
                    $parameters[] = "{$paramName}={$value}";
                    $addedParams[] = $paramName;
                }
            }
        }

        // logger($parameters);

        $paramString = !empty($parameters) ? ' ' . implode(', ', $parameters) : '';
        $execStatement = "EXEC {$spName}" . $paramString;
        // logger($spCommand);
        // logger($execStatement);

        // Execute the stored procedure
        try {
            $data = DB::connection('sqlsrv_conn_dyn')
                ->select("SET NOCOUNT ON; {$execStatement}");

            $result = [
                'data' => json_decode(json_encode($data), true),
                'page' => 1,
                'rowsNumber' => count($data),
                'query' => $execStatement,
                'status' => true
            ];

            return $request->has('pagination')
                ? $result
                : $data;
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'SP Execution failed: ' . $e->getMessage()];
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

        $getData = $this->showHistory(new Request([
            'filter' => $filter,
            'pagination' => $request->pagination
        ]), $report->mrm_query);

        // return $getData;
        // // Decode the response content to access data
        $responseData = json_decode($getData->getContent(), true);
        if ($responseData['status'] === false) {
            return $this->handleError('CMS URL report generation failed: ' . $responseData['message']);
        }

        // Assuming the response contains 'data' key with the report data
        $data = $responseData['data'] ?? [];

        return $request->has('pagination') ? $data : $data['data'];

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

            return $this->handleResponse($data, 'Data report found');

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
            // if ($filter['opr'] === '<cols>') {
            //     $otherColumn = $this->quoteIdentifier($values[0]['value']);
            //     $query->$whereMethod("{$column} <> {$otherColumn}");
            //     continue;
            // }

            // Handle regular value comparisons
            $paramPrefix = 'filter_' . uniqid();

            $val1 = $this->valueChecker($values[0]);
            $val2 = $this->valueChecker($values[1] ?? $values[0]);

            switch ($filter['opr']) {
                case 'between':
                    $query->$whereMethod("{$column} BETWEEN :{$paramPrefix}_1 AND :{$paramPrefix}_2")
                        ->setParameter("{$paramPrefix}_1", $val1, $isNumeric ? \PDO::PARAM_INT : \PDO::PARAM_STR)
                        ->setParameter("{$paramPrefix}_2", $val2, $isNumeric ? \PDO::PARAM_INT : \PDO::PARAM_STR);
                    break;

                case 'like':
                    $query->$whereMethod("{$column} LIKE :{$paramPrefix}")
                        ->setParameter($paramPrefix, '%' . $val1 . '%', \PDO::PARAM_STR);
                    break;

                case '>':
                    $query->$whereMethod("{$column} > :{$paramPrefix}")
                        ->setParameter($paramPrefix, $val1, $isNumeric ? \PDO::PARAM_INT : \PDO::PARAM_STR);
                    break;
                case '<':
                    $query->$whereMethod("{$column} < :{$paramPrefix}")
                        ->setParameter($paramPrefix, $val1, $isNumeric ? \PDO::PARAM_INT : \PDO::PARAM_STR);
                    break;
                case '=':
                    $query->$whereMethod("{$column} = :{$paramPrefix}")
                        ->setParameter($paramPrefix, $val1, $isNumeric ? \PDO::PARAM_INT : \PDO::PARAM_STR);
                    break;
                case '>=':
                    $query->$whereMethod("{$column} >= :{$paramPrefix}")
                        ->setParameter($paramPrefix, $val1, $isNumeric ? \PDO::PARAM_INT : \PDO::PARAM_STR);
                    break;
                case '<=':
                    $query->$whereMethod("{$column} <= :{$paramPrefix}")
                        ->setParameter($paramPrefix, $val1, $isNumeric ? \PDO::PARAM_INT : \PDO::PARAM_STR);
                    break;
                case '<>':
                    $query->$whereMethod("{$column} <> :{$paramPrefix}")
                        ->setParameter($paramPrefix, $val1, $isNumeric ? \PDO::PARAM_INT : \PDO::PARAM_STR);
                    break;
                case '<cols>':
                    // Handled above as a special case
                    $otherColumn = $this->quoteIdentifier($values[0]['value']);
                    $query->$whereMethod("{$column} <> {$otherColumn}");
                    break;
                case 'likecols':
                    $otherColumn = $this->quoteIdentifier($values[0]['value']);
                    $query->$whereMethod("{$column} LIKE {$otherColumn}");
                    break;
                case '=cols>':
                    $otherColumn = $this->quoteIdentifier($values[0]['value']);
                    $query->$whereMethod("{$column} = {$otherColumn}");
                    break;
                case '<cols':
                    $otherColumn = $this->quoteIdentifier($values[0]['value']);
                    $query->$whereMethod("{$column} < {$otherColumn}");
                    break;
                case '<=cols':
                    $otherColumn = $this->quoteIdentifier($values[0]['value']);
                    $query->$whereMethod("{$column} <= {$otherColumn}");
                    break;
                case '>cols':
                    $otherColumn = $this->quoteIdentifier($values[0]['value']);
                    $query->$whereMethod("{$column} > {$otherColumn}");
                    break;
                case '>=cols':
                    $otherColumn = $this->quoteIdentifier($values[0]['value']);
                    $query->$whereMethod("{$column} >= {$otherColumn}");
                    break;
                case 'isnull':
                    $query->$whereMethod("{$column} IS NULL");
                    break;
                case 'isnotnull':
                    $query->$whereMethod("{$column} IS NOT NULL");
                    break;

                default:
                    Log::warning('Unsupported operator', ['operator' => $filter['opr']]);
            }
        }

        logger('Applied filter', ['column' => $column, 'operator' => $filter['opr'], 'values' => $values]);
    }

    public function valueChecker($val)
    {
        if ($val === 'today()') {
            return date('Y-m-d');
        } elseif ($val === 'now()') {
            return date('Y-m-d H:i:s');
        } elseif ($val === 'tomorrow()') {
            return date('Y-m-d', strtotime('+1 day'));
        } elseif ($val === 'yesterday()') {
            return date('Y-m-d', strtotime('-1 day'));
        } elseif (is_numeric($val)) {
            return (float) $val;
        } else {
            return "'" . $val . "'";
        }
    }

    public function exportToExcel($idReport, Request $request)
    {
        $cekReport = MRSReportMstr::select(
            'mrs_report_mstr.*'
        )
            ->where('mrs_report_mstr.id', $idReport)
            ->first();

        $response = $this->runningReport($idReport, $request);

        $getData = json_decode($response->getContent(), true);

        if (!$getData || !isset($getData['data'])) {
            return $this->handleError('Failed to generate report data');
        }

        $filename = 'export_' . $cekReport->mrm_name . '_' . date('ymd_his') . '.xlsx';

        Excel::store(new ExportReport($getData['data'], $idReport), 'MRS/' . $filename, 'public');

        return [
            'status' => true,
            'path' => '/storage/MRS/' . $filename,
        ];
    }

    public function getListAPIColection($idReport)
    {
        $checkQuota = $this->getDataGencode(
            'MRS_API_GEN',
            [
                'pgm_value3' => $idReport
            ],
            [
                'token' => 'pgm_value|string',
                'filter' => 'pgm_value2|array',
                'id_report' => 'pgm_value3|int',
                'created_at' => 'created_at|datetime',
            ],
            [],
            false
        );

        return $this->handleResponse([
            'used' => count($checkQuota),
            'data' => $checkQuota
        ], 'Data Found');
    }

    public function storeSearchForAPI(Request $request)
    {
        $checkQuota = $this->getDataGencode(
            'MRS_API_GEN',
            [
                'pgm_value2' => json_encode($request->filter),
                'pgm_value3' => $request->id_report
            ],
            [],
            [],
            false
        );

        if (count($checkQuota) > (int) $request->max_api_opt) {
            return $this->handleError('API Search Quota exceeded. Please contact administrator.');
        }

        $store = $this->saveGencode(
            new Request([
                'data' => [
                    'pgm_code' => 'MRS_API_GEN',
                    'pgm_value' => uniqid('MRSAPI_'),
                    'pgm_value2' => json_encode($request->filter),
                    'pgm_value3' => $request->id_report,
                    'created_by' => $request->user_id,
                    'pgm_desc' => 'MRS API Report Generated',
                ]
            ])
        );
    }
}
