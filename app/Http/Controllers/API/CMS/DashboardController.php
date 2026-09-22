<?php

namespace App\Http\Controllers\API\CMS;

use App\Http\Controllers\API\PORTAL\BaseController;
use App\Models\CMS\DashboardMstr;
use Illuminate\Http\Request;

class DashboardController extends BaseController
{
    /**
     * List all dashboards (management screen).
     */
    public function index()
    {
        $data = DashboardMstr::orderBy('cdm_title')->get();
        return $this->handleResponse($data, 'Data Found !');
    }

    /**
     * Display the specified dashboard (editor load).
     */
    public function show($id)
    {
        $data = DashboardMstr::find($id);
        if (!$data) {
            return $this->handleError('Dashboard not found', 404);
        }
        return $this->handleResponse($data, 'Data Found !');
    }

    /**
     * Create or update a dashboard (upsert by idRef, same pattern as cms/forms).
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:200',
            'code' => 'required|string|max:100|regex:/^[a-z0-9][a-z0-9\-_]*$/',
        ]);

        $idRef = $request->idRef;

        $clash = DashboardMstr::where('cdm_code', $request->code)
            ->when($idRef, fn ($q) => $q->where('id', '<>', $idRef))
            ->first();
        if ($clash) {
            return $this->handleError('Dashboard code is already used by another dashboard.', 422);
        }

        $layout = $request->input('layout', []);
        if (!is_array($layout)) {
            return $this->handleError('Invalid layout', 422);
        }

        $dashboard = DashboardMstr::updateOrCreate(
            ['id' => $idRef],
            [
                'p_u_username' => $request->header('username'),
                'cdm_code' => $request->code,
                'cdm_title' => $request->title,
                'cdm_desc' => $request->desc,
                'cdm_layout' => json_encode($layout),
                'cdm_roles' => json_encode($this->normalizeRoles($request->roles)),
                'cdm_status' => $request->input('status', 'draft'),
            ]
        );

        return $this->handleResponse($dashboard, 'Dashboard saved successfully');
    }

    /**
     * Soft delete a dashboard.
     */
    public function destroy($id)
    {
        $dashboard = DashboardMstr::find($id);
        if (!$dashboard) {
            return $this->handleError('Dashboard not found', 404);
        }
        $dashboard->delete();
        return $this->handleResponse([], 'Dashboard deleted successfully');
    }

    /**
     * Public render endpoint used by the CMS widget and previews. Enforces
     * the dashboard's role allowlist via the roleid header, mirroring
     * FormController::pageRoleDenied.
     */
    public function viewByCode(Request $request, $code)
    {
        $dashboard = DashboardMstr::where('cdm_code', $code)->first();
        if (!$dashboard) {
            return $this->handleError('Dashboard not found', 404);
        }

        $roles = $this->decodeJson($dashboard->cdm_roles) ?: [];
        if (count($roles) > 0) {
            $roleId = trim((string) $request->header('roleid'));
            if ($roleId === '' || !in_array($roleId, $roles, true)) {
                return response([
                    'status' => false,
                    'message' => 'You do not have access to this dashboard.',
                ], 403);
            }
        }

        return $this->handleResponse([
            'id' => $dashboard->id,
            'code' => $dashboard->cdm_code,
            'title' => $dashboard->cdm_title,
            'desc' => $dashboard->cdm_desc,
            'status' => $dashboard->cdm_status,
            'layout' => $this->decodeJson($dashboard->cdm_layout) ?: ['items' => []],
        ], 'Data Found !');
    }

    /* ------------------------------------------------------------------ */

    private function normalizeRoles($roles): array
    {
        if (!is_array($roles)) {
            return [];
        }
        return array_values(array_filter(array_map('strval', $roles), fn ($r) => $r !== ''));
    }

    private function decodeJson($value)
    {
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }
}
