<?php

namespace App\Http\Controllers\PORTAL\Customs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\PORTAL\CUSTOMS\CircularTen;
use App\Models\PORTAL\DOCCREATOR\ContentCreator;
use App\Models\DMS\Core\DocsLocationMaster;
use App\Models\PORTAL\DOCCREATOR\ContentDefine;

use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

use SnappyPDF;
use Illuminate\Support\Str;

class CircullarTenController extends Controller
{
    public function store(Request $req)
    {
        //Create Content
        $ceklast = ContentCreator::where('content_creator_id', 'like', 'PCCRTR' . date('ymd') . '%')->orderBy('content_creator_id', 'desc')->first();
        if (empty($ceklast)) {
            $nextid = 'PCCRTR' . date('ymd') . '0001';
        } else {
            $nextid = 'PCCRTR' . date('ymd') . sprintf('%04d', (int) substr($ceklast['content_creator_id'], -3) + 1);
        }

        $form = json_decode(json_encode($req->forms), true);
        // return $req->forms;
        foreach ($form as $key_form => $value_form) {
            ContentCreator::create([
                'content_var_id' => $key_form,
                'content_var_value' => $value_form,
                'content_users' => $req->users,
                'content_creator_id' => $nextid
            ]);
        }

        // Buat content
        $hasil = [];
        foreach ($req->docs as $key => $value) {
            $hasil[] = CircularTen::create([
                'creator_id' => $nextid,
                'doc_id' => $value['FILE']
            ]);
        }

        ContentDefine::create([
            'content_mstr_id' => $nextid,
            'apprv_mstr_id' => $req->content['id']
        ]);

        return 'CIRCULAR_TEN_' . date('ymdhis');
    }

    public function testSnappy()
    {
        $pdf = App::make('snappy.pdf.wrapper');
        $pdf->loadHTML('
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    .t-lines {
                        display: table
                    }
                    .t2{
                        font-weight: bold
                    }
                </style>
            </head>
            <body>
                <a href="link.html">
                    <span class="t-lines">
                        test1
                    </span>
                </a>
                <p>
                    These links works:
                </p>
                <a href="link.html">
                    test2
                </a>
                <a href="link.html" class="t-lines">
                    test3
                </a>
                <a href="link.html">
                    <div class="t-lines">
                        test4
                    </div>
                </a>
                <a href="link.html">
                    <span class="t2">
                        test5
                    </span>
                </a>
            </body>
            </html>
        ');
        return $pdf->inline();
    }

    public function htmlConvertToPDF($html, $pdfname)
    {
        $pdf = App::make('snappy.pdf.wrapper');
        $pdf->loadHTML($html);

        $pdf->save($pdfname . '.pdf');
    }

    public function getfullpathfolder($id)
    {
        function recfolder($arr, $id)
        {
            $cek = [];
            if ($arr['all_parent_folder'] !== null) {
                $cek = recfolder($arr['all_parent_folder'], $arr['parent_loc']);
            }

            $cek[] = $arr['name_loc'];

            return $cek;
        }

        $hasil = recfolder(DocsLocationMaster::where('id', $id)->with('allParentFolder')->first()->toArray(), $id);
        // return $hasil;
        $jointopath = implode('/', $hasil);

        return $jointopath;
    }

    public function getcirten()
    {
        return CircularTen::get();
    }

    public function uploadCirtenAttachment(Request $req)
    {
        $nama_file = $req->file->getClientOriginalName();
        $rootfolder = 'Uploaded Docs/Circular Ten/'.$req->header('username'). '/' . date('Y') . '/' . date('F') . '/' . date('D') . ' - ' . date('d') . '/';
        if (count($this->checkfile($nama_file)) == 0) {
            $req->file->storeAs($rootfolder, $nama_file);

            return 'success';
        } else {
            return response('File ' . $nama_file . ' exists, please add other file !', 422);
        }
    }

    public function cekallfiles()
    {
        $dir    = env('CIRCULAR_TEN_LOC');

        function dirToArray($dir)
        {
            $cdir = scandir($dir);
            $result = [];
            foreach ($cdir as $key => $value) {
                if (!in_array($value, array(".", ".."))) {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
                        $result[] = dirToArray($dir . DIRECTORY_SEPARATOR . $value);
                    } else {
                        $result[] = $value;
                    }

                    // if (!is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
                    //     $result[] = $value;
                    // }
                }
            }
            return $result;
        }

        return dirToArray($dir);
    }

    public function cekallfileswithpath($user = null)
    {
        $dir    = $user == null ? env('CIRCULAR_TEN_LOC') : env('CIRCULAR_TEN_LOC').$user.'/';

        function getDirContents($dir, &$results = array(), &$count = 0)
        {
            $files = scandir($dir);

            foreach ($files as $key => $value) {
                $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
                if (!is_dir($path)) {
                    // $cekdatacreated
                    $results[$count]['PATH'] = $path;
                    $results[$count]['FILE'] = $value;
                    $results[$count]['MODIFIED'] = date('Y-m-d H:i:s', filemtime($path));
                    $count++;
                } else if ($value != "." && $value != "..") {
                    getDirContents($path, $results, $count);
                }
            }

            return $results;
        }

        return getDirContents($dir);
    }

    public function checkfile($filename)
    {
        $allfilesonfolder = json_decode(json_encode($this->cekallfiles()), true);

        function flatten($array)
        {
            return array_reduce($array, function ($acc, $item) {
                return array_merge($acc, is_array($item) ? flatten($item) : [$item]);
            }, []);
        }

        if ($filename == 'all') {
            return flatten($allfilesonfolder);
        } else {
            $hasil = [];
            foreach (flatten($allfilesonfolder) as $key => $value) {
                if ($value === $filename) {
                    $hasil[] = $value;
                }
            }

            return $hasil;
        }
    }

    public function deletefiles(Request $req)
    {
        $cekdata = CircularTen::where('doc_id', $req['files']['FILE'])->first();

        if (empty($cekdata)) {
            if (File::exists($req['files']['PATH'])) {
                File::delete($req['files']['PATH']);
                return 'delete success';
            } else {
                return response([
                    'message' => "The given data was invalid.",
                    'errors' => [
                        'menu_id' => ["Error: File not found, cannot delete it!!"]
                    ]
                ], 422);
            }
        } else {
            if ($req->has('force')) {
                File::delete($req['files']['PATH']);
                return 'delete success';
            }
            
            return response([
                'message' => "The given data was invalid.",
                'errors' => [
                    'menu_id' => ["Error: This file used by Circular Ten!!"]
                ]
            ], 422);
        }
    }

    public function getfiles($path, $name = null)
    {
        $headers = array(
            'Content-Description: File Transfer',
            'Content-Type: application/octet-stream',
            'Content-Disposition: attachment; filename="' . $name . '"',
        );
        if (File::exists(base64_decode($path))) {
            return response()->file(base64_decode($path), $headers);
        } else {
            return view('PORTAL/filenotfound');
            return 'Files not found';
        }
    }
}
