<?php

namespace App\Http\Controllers\API\RPA;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\PORTAL\PortalRPAMaster;

class RPAMasterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = PortalRPAMaster::with('prmParameter')->get();
        return response()->json($data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($request->has('prh_id')) {
            return $this->update($request, $request->prh_id);
        }
        $data = PortalRPAMaster::create($request->all());

        // Save related prmParameter if provided
        if ($request->has('prm_parameter') && is_array($request->prm_parameter)) {
            foreach ($request->prm_parameter as $param) {
                $data->prmParameter()->create($param);
            }
        }

        if ($request->has('prm_command') && is_array($request->prm_command)) {
            foreach ($request->prm_command as $cmd) {
                $data->prmCommand()->create($cmd);
            }
        }

        return response()->json($data, 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = PortalRPAMaster::findOrFail($id);
        $data->update($request->all());

        // Update related prmParameter if provided
        if ($request->has('prm_parameter') && is_array($request->prm_parameter)) {
            // Optionally, you may want to delete existing and recreate, or update individually
            $data->prmParameter()->delete();
            foreach ($request->prm_parameter as $param) {
                $data->prmParameter()->create($param);
            }
        }

        if ($request->has('prm_command') && is_array($request->prm_command)) {
            $data->prmCommand()->delete();
            foreach ($request->prm_command as $cmd) {
                function insertCMD($dataCMD, $id = '', $data = null) {
                    $createData = $data->prmCommand()->create(
                        array_merge(
                            $dataCMD, 
                            [
                                'prcd_parentsid' => $id,
                                'prcd_action' => isset($dataCMD['prcd_action']) ? json_encode($dataCMD['prcd_action']) : null,
                            ]
                        )
                    );

                    if (isset($dataCMD['prcd_children']) && is_array($dataCMD['prcd_children'])) {
                        $children = $dataCMD['prcd_children'];
                        insertCMD($children, $createData->id, $data);
                        unset($dataCMD['prcd_children']);
                    } else {
                        $children = [];
                    }

                    return $dataCMD;
                }

                insertCMD($cmd, '', $data);
            }
        }
        return response()->json($data, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $data = PortalRPAMaster::findOrFail($id);
        $data->delete();
        return response()->json(null, 204);
    }
}
