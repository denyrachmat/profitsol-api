<?php

namespace App\Http\Controllers\API\MOBILE;

use Illuminate\Http\Request;
use App\Models\PORTAL\PortalGencode;
use App\Http\Controllers\API\PORTAL\BaseController as BaseController;

class LabelManagerController extends BaseController
{
    protected $code = 'MBL_PRINT_TEMPLATE';

    /**
     * Display a listing of the label templates.
     */
    public function index()
    {
        $data = PortalGencode::where('pgm_code', $this->code)
            ->orderBy('pgm_desc', 'asc')
            ->get()
            ->map(function ($g) {
                return $this->mapTemplate($g);
            });

        return $this->handleResponse($data, 'Label templates found !');
    }

    /**
     * Store a new label template (or update existing by pgm_value).
     */
    public function store(Request $request)
    {
        $row = $this->saveTemplate($request);

        return $this->handleResponse($this->mapTemplate($row), 'Label template saved !');
    }

    /**
     * Display the specified label template.
     */
    public function show(string $id)
    {
        $g = PortalGencode::where('pgm_code', $this->code)
            ->where('pgm_value', $id)
            ->first();

        if (!$g) {
            return $this->handleError('Label template not found.', []);
        }

        return $this->handleResponse($this->mapTemplate($g), 'Label template found !');
    }

    /**
     * Update the specified label template.
     */
    public function update(Request $request, string $id)
    {
        $request->merge(['id' => $id]);
        $row = $this->saveTemplate($request);

        return $this->handleResponse($this->mapTemplate($row), 'Label template updated !');
    }

    /**
     * Remove (soft-delete) the specified label template.
     */
    public function destroy(string $id)
    {
        $g = PortalGencode::where('pgm_code', $this->code)
            ->where('pgm_value', $id)
            ->first();

        if (!$g) {
            return $this->handleError('Label template not found.', []);
        }

        $g->delete();

        return $this->handleResponse([], 'Label template deleted !');
    }

    /**
     * Upsert a template row from request payload.
     */
    private function saveTemplate(Request $request): PortalGencode
    {
        $pgmValue = (string) ($request->input('id') ?: uniqid('LBL_', true));

        $template  = $request->input('template');
        $contract  = $request->input('contract');

        return PortalGencode::updateOrCreate(
            [
                'pgm_code'  => $this->code,
                'pgm_value' => $pgmValue,
            ],
            [
                'pgm_code'   => $this->code,
                'pgm_value'  => $pgmValue,
                'pgm_desc'   => $request->input('name'),
                'pgm_desc2'  => $request->input('language', 'ZPL'),
                'pgm_desc3'  => $request->input('description'),
                'pgm_value2' => is_array($template) ? json_encode($template) : $template,
                'pgm_value3' => is_array($contract) ? json_encode($contract, JSON_UNESCAPED_UNICODE) : $contract,
                'pgm_created_by' => $request->header('username') ?? $request->input('created_by'),
            ]
        );
    }

    /**
     * Normalize a gencode row to the label-template object shape.
     */
    private function mapTemplate(PortalGencode $g): array
    {
        return [
            'id'          => $g->id,
            'name'        => $g->pgm_desc,
            'language'    => $g->pgm_value3,
            'description' => $g->pgm_desc2,
            'template'    => $g->pgm_value,
            'config'      => json_decode($g->pgm_value2, true) !== null ? json_decode($g->pgm_value2, true) : $g->pgm_value2,
            'contract'    => json_decode($g->pgm_value3, true) ?: $g->pgm_value3,
        ];
    }
}
