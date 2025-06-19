<?php

namespace App\Http\Controllers\API\RPA;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PORTAL\PortalRPAHist;
use App\Http\Controllers\API\PORTAL\BaseController;
use App\Jobs\RPA\SendRPAJobsQueue;

class RPAHistController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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

        $data = PortalRPAHist::create($request->all());

        // If the request contains a 'prh_cfaud_id', ensure it's an integer
        if ($request->has('prh_cfaud_id')) {
            $data->prh_cfaud_id = (int) $request->prh_cfaud_id;
            $data->save();
        }


        // Dispatch the job to send the RPA history data to an external API
        SendRPAJobsQueue::dispatch($data->id)->onQueue('rpa_jobs');

        return $this->handleResponse($data, 'RPA History created successfully.');
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
        try {
            $data = PortalRPAHist::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->handleError('RPA History not found.', 404);
        }

        if (!$request->has('prh_flag')) {

            $data->update([
                'prh_result' => 'Trying resubmit RPA Job',
            ]);

            // If the request contains a 'prh_cfaud_id', ensure it's an integer
            if ($request->has('prh_cfaud_id')) {
                $data->prh_cfaud_id = (int) $request->prh_cfaud_id;
            }

            // Dispatch the job to send the RPA history data to an external API
            SendRPAJobsQueue::dispatch($id)->onQueue('rpa_jobs');
        }

        $data->save();

        // $this->sendApi($id);

        return $this->handleResponse($data, 'RPA History updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
