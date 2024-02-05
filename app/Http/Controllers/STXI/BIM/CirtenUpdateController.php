<?php

namespace App\Http\Controllers\STXI\BIM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Excel;
use Carbon\Carbon;

use App\Models\STXI\BIM\CircularTenMstr;
use App\Models\STXI\BIM\CircularTenModelDet;
use App\Models\STXI\BIM\CircularTenPathHtm;

use App\Imports\STXI\BIM\ImportCircularTen;

class CirtenUpdateController extends Controller
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
        $hasil = [];

        foreach ($request->data as $key => $value) {
            $file = $value['path'].'/'.$value['tenNum'].'.xlsx';

            if (Storage::disk('ten_bim')->exists($file)) {
                // $files = mb_convert_encoding( Storage::disk('ten_bim')->get($file), 'UTF-8', 'UTF-8');

                $importer = new ImportCircularTen();

                Excel::import($importer, $file, 'ten_bim');
                
                $hasil[] = [
                    'status' => true,
                    'files' => $importer->data
                ];
            } else {
                $hasil[] = [
                    'status' => false,
                    'files' => null
                ];
            }
        }

        return $hasil;
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function showByDateTen($date){
        $getYear = date('Y', strtotime($date));
        $getMonth = date('m', strtotime($date));
        $listData = Storage::disk('ten_bim')->directories($getYear.'/'.$getMonth);

        $hasil = [];
        foreach ($listData as $key => $value) {
            preg_match('#\((.*?)\)#', $value, $tenNum);

            $getListFolderName = explode('/', $value);
            $getFolderName = $getListFolderName[count($getListFolderName) - 1];

            if (isset($tenNum[1])) {
                $hasil[] = [
                    'foldername' => $getFolderName,
                    'tenNum' => $tenNum[1],
                    'path' => $value,
                ];
            }
        }

        return $hasil;
    }
}
