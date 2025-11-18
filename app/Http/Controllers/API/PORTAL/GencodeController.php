<?php

namespace App\Http\Controllers\API\PORTAL;

use App\Http\Controllers\API\PORTAL\BaseController;
use Illuminate\Http\Request;
use App\Models\PORTAL\PortalGencode;
use App\Traits\PORTAL\GencodeTraits;

class GencodeController extends BaseController
{
    use GencodeTraits;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => PortalGencode::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = PortalGencode::updateOrCreate([
            'id' => $request->id,
        ],[
            'pgm_code' => $request->pgm_code,
            'pgm_desc' => $request->pgm_desc,
            'pgm_desc2' => $request->pgm_desc2,
            'pgm_desc3' => $request->pgm_desc3,
            'pgm_value' => $request->pgm_value,
            'pgm_value2' => $request->pgm_value2,
            'pgm_value3' => $request->pgm_value3,
            'pgm_created_by' => $request->header('username'),
        ]);

        return $this->handleResponse(
            $data,
            'Store Successfull !'
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return $this->handleResponse(
            $this->getDataGencode($id),
            'Data Found !'
        );
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return PortalGencode::where('pgm_code', $id)->delete()
            ? $this->handleResponse([], 'Delete Successfull !')
            : $this->handleError('Delete Failed !');
    }

    public function showDetail($id, Request $request)
    {
        $data = $this->getDataGencode(
                $id, 
                $request->filter ?? [], 
                $request->selectAs ?? [], 
                $request->orderBy ?? [], 
                $request->firstSelect ?? false, 
                $request->withParents ?? false, 
                $request->forceShowAll ?? false, 
                $request->groupBy ?? []
        );
        
        return $this->handleResponse(
            array_values($data),
            'Data Found !'
        );
        // This method is currently empty, you can implement it as needed.
    }

    public function deleteDetail($id, Request $request)
    {
        $data = PortalGencode::where('pgm_code', $id);

        if ($request->has('filter')) {
            foreach ($request->filter as $key => $value) {
                $data->where($value['column'], $value['operator'], $value['value']);
            }
        }

        $checkData = (clone $data)->first();

        if (!$checkData) {
            return $this->handleError('Data not found', 404);
        }

        $data->delete();

        return $this->handleResponse([], 'Data deleted successfully');
    }
}
