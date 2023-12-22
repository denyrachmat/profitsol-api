<?php

namespace App\Http\Controllers\STXI\LOG;

use App\Http\Controllers\API\PORTAL\BaseController;
use App\Models\STXI\CEISA40\CEISARESPON;
use App\Models\STXI\LOG\EntitasMaster;
use App\Models\STXI\LOG\EntitasSkepDetail;
use Illuminate\Http\Request;
use Excel;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Imports\STXI\LOG\ImportCeisa40;
use App\Models\STXI\LOG\CeisaToken;

use App\Traits\STXI\LOG\Ceisa40Traits;

class Ceisa40UploaderController extends BaseController
{
    use Ceisa40Traits;
    public function uploadData(Request $req)
    {
        ini_set('max_execution_time', '300');
        // $nama_file = $req->file->hashName();
        $file = new File($req->file);
        $extNya = $req->file('file')->getClientOriginalExtension();
        $realFileName = $req->file('file')->getClientOriginalName();

        $fileHash = str_replace('.' . $file->extension(), '', $file->hashName());
        $nama_file = $fileHash . '.' . $extNya;

        // return $nama_file;

        $req->file->storeAs('/public/upload_ceisa40/', $nama_file);

        if ($extNya == 'xls') {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $writer = new Xlsx($spreadsheet);
            $nama_file = $fileHash . '.xlsx';
            $writer->save('/public/upload_ceisa40/' . $nama_file);
        }


        if (str_contains($realFileName, '1.6') || str_contains($realFileName, '2.7I') || str_contains($realFileName, '4.0')) {
            logger('ini incoming !!');
            $state = 'INC';
        } else {
            $state = 'OUT';
        }

        $importer = new ImportCeisa40($state);

        Excel::import($importer, public_path('/storage/upload_ceisa40/' . $nama_file));

        return $this->handleResponse([], 'Upload Sukses ' . $nama_file);
    }
}
