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
            ->where('id', $id)
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
            ->where('id', $id)
            ->first();

        if (!$g) {
            return $this->handleError('Label template not found.', []);
        }

        $g->delete();

        return $this->handleResponse([], 'Label template deleted !');
    }

    /**
     * Upsert a template row from request payload.
     * Correct mapping to match PartScannerController::listLabels and legacy row 10485:
     *  pgm_value  = template (ZPL/SBPL)
     *  pgm_value2 = config (JSON contract, accepts `config` or `contract`)
     *  pgm_value3 = language/format (ZPL/SBPL)
     *  pgm_desc   = name
     *  pgm_desc2  = description
     */
    private function saveTemplate(Request $request): PortalGencode
    {
        $id = $request->input('id');
        $template  = $request->input('template');
        // frontend sends `config`, older payloads may use `contract`
        $config = $request->input('config') ?? $request->input('contract');
        $language = $request->input('language') ?? $request->input('format') ?? 'ZPL';

        $attrs = [
            'pgm_code'   => $this->code,
            'pgm_value'  => is_array($template) ? json_encode($template) : $template,
            'pgm_value2' => is_array($config) ? json_encode($config, JSON_UNESCAPED_UNICODE) : (is_string($config) ? $config : json_encode($config, JSON_UNESCAPED_UNICODE)),
            'pgm_value3' => $language,
            'pgm_desc'   => $request->input('name'),
            'pgm_desc2'  => $request->input('description'),
            'pgm_created_by' => $request->header('username') ?? $request->input('created_by'),
        ];

        if ($id) {
            $g = PortalGencode::where('id', $id)->where('pgm_code', $this->code)->first();
            if ($g) {
                $g->update($attrs);
                return $g->fresh();
            }
            // fallback: if id not found (e.g. piggy pgm_value lookup from old client), try pgm_value
            $gByValue = PortalGencode::where('pgm_code', $this->code)->where('pgm_value', (string) $id)->first();
            if ($gByValue) {
                $gByValue->update($attrs);
                return $gByValue->fresh();
            }
        }

        // create new
        return PortalGencode::create(array_merge(['pgm_code' => $this->code], $attrs));
    }

    /**
     * Normalize a gencode row to the label-template object shape.
     * Mirrors PartScannerController::listLabels column mapping:
     *  template=pgm_value, config=pgm_value2, format/language=pgm_value3, name=pgm_desc, desc=pgm_desc2
     */
    private function mapTemplate(PortalGencode $g): array
    {
        $cfg = json_decode($g->pgm_value2, true);
        $cfgDecoded = json_last_error() === JSON_ERROR_NONE ? $cfg : $g->pgm_value2;
        return [
            'id'          => $g->id,
            'name'        => $g->pgm_desc,
            'language'    => $g->pgm_value3,
            'description' => $g->pgm_desc2,
            'template'    => $g->pgm_value,
            'config'      => $cfgDecoded,
            'contract'    => $cfgDecoded,
        ];
    }
}
