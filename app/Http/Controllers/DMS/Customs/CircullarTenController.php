<?php

namespace App\Http\Controllers\DMS\Customs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\DMS\Custom\CircularTen;
use App\Models\DMS\Core\ContentCreator;
use App\Models\DMS\Core\DocsMaster;
use App\Models\DMS\Core\VerMaster;
use App\Models\DMS\Core\DocsLocationMaster;

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
        $ceklast = ContentCreator::where('content_creator_id', 'like', 'CRTR' . date('ymd') . '%')->orderBy('content_creator_id', 'desc')->first();
        if (empty($ceklast)) {
            $nextid = 'CRTR' . date('ymd') . '0001';
        } else {
            $nextid = 'CRTR' . date('ymd') . sprintf('%04d', (int) substr($ceklast['content_creator_id'], -3) + 1);
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

        return 'CIRCULAR_TEN_' . date('ymdhis');

        $cekfolder = $this->getfullpathfolder(DocsLocationMaster::where('id', $req->folder)->with('allChildFolder')->first()->toArray());

        //Save pdf nya
        $folder = 'd:/data/Uploaded Docs/DMS/' . $req->users . '/' . $cekfolder . '/';

        $pdfrootname = 'CIRCULAR_TEN_' . date('ymd');
        $cekdoc = DocsMaster::where('doc_real_path', $folder)->where('doc_real_name', 'like', $pdfrootname)->orderBy('created_at', 'desc')->first();

        if (empty($cekdoc)) {
            $pdfname = $pdfrootname . '0001';
        } else {
            $pdfname = sprintf('%04d', (int) substr($cekdoc['doc_real_name'], -3) + 1);
        }

        $nama_file = uniqid('DMSDOC_') . rand();

        $this->htmlConvertToPDF($req->parseHTML, $folder . $nama_file);

        //Save ke doc master.
        $id_document = Str::random(50);
        DocsMaster::create([
            'doc_id' => $id_document,
            'doc_name' => $nama_file . '.pdf',
            'doc_path' => $req->folder,
            'doc_real_path' => 'Uploaded Docs/DMS/' . $req->users . '/' . $cekfolder . '/',
            'doc_author' => $req->users,
            'doc_real_name' => $pdfname . '.pdf',
            'doc_size' => File::size($folder . $nama_file . '.pdf'),
            'doc_lapprv_flag' => "1"
        ]);

        //Save ke versioning document
        VerMaster::create([
            'id' => Str::random(50),
            'ver_docnm' => $id_document,
            'ver_docloc' => $req->folder,
            'ver_code' => 0,
            'ver_comment' => 'First Circular Technical Upload',
        ]);
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
        // return $req->file->getClientOriginalName();
        $nama_file = $req->file->getClientOriginalName();
        $rootfolder = 'Uploaded Docs/Circular Ten/' . date('Y') . '/' . date('F') . '/' . date('D') . ' - ' . date('d') . '/';
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

    public function cekallfileswithpath()
    {
        $dir    = env('CIRCULAR_TEN_LOC');

        function getDirContents($dir, &$results = array())
        {
            $files = scandir($dir);

            $count = 0;
            foreach ($files as $key => $value) {
                $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
                if (!is_dir($path)) {
                    // $cekdatacreated
                    $results[$count]['PATH'] = $path;
                    $results[$count]['FILE'] = $value;
                    $results[$count]['MODIFIED'] = date('Y-m-d H:i:s', filemtime($path));
                    $count++;
                } else if ($value != "." && $value != "..") {
                    getDirContents($path, $results);
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
            if (File::get($req['files']['PATH'])) {
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

        return response()->file(base64_decode($path), $headers);
    }
}
