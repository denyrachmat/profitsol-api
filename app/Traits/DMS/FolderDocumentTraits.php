<?php

namespace App\Traits\DMS;

use App\Models\DMS\DMSFolderMstr;
use App\Models\DMS\DMSDocMstr;
use App\Models\DMS\DMSFolderRootMstr;
use Illuminate\Support\Facades\Storage;

trait FolderDocumentTraits
{
    public function getFolder($author, $id = null)
    {
        $users = $this->getAliasFolderbyAuthor($author);

        $dataFolder = DMSFolderMstr::with('childFolders')->with('doc')->where('p_u_username' ,$users)->whereNull('dfm_parent_id');
        $dataFiles = DMSDocMstr::where('p_u_username' ,$users);

        return !empty($id)
        ? [
            'child_folders' => $dataFolder->where('id', $id)->get(),
            'doc' => $dataFiles->where('dfm_id' ,$id)->get()
        ]
        : [
            'child_folders' => $dataFolder->get(),
            'doc' => $dataFiles->whereNull('dfm_id')->get()
        ];
    }

    public function getAliasFolderbyAuthor($author)
    {
        $checkRootAlias = DMSFolderRootMstr::where('p_u_username' ,$author)->first();

        $users = empty($checkRootAlias) ? $author : $checkRootAlias->dudrm_path;

        return $users;
    }

    public function pathCreator($data, $hasil = '')
    {
        $hasil = $data['dfm_folder_name'];
        if (!empty($data['parent_folders'])) {
            return $this->pathCreator($data['parent_folders'], $data['dfm_folder_name']). '/'. $hasil;
        }

        return $hasil;
    }

    public function createNewFolder($author, $path)
    {
        return Storage::disk('data_folder_local')->makeDirectory('DMS/'.$this->getAliasFolderbyAuthor($author).'/'.$path);
    }

    public function deleteFolder($author, $path)
    {
        return Storage::disk('data_folder_local')->deleteDirectory('DMS/'.$this->getAliasFolderbyAuthor($author).'/'.$path);
    }

    public function deleteFiles($author, $path, $file)
    {
        return Storage::disk('data_folder_local')->delete('DMS/'.$this->getAliasFolderbyAuthor($author).'/'.$path.'/'.$file);
    }

    public function openFiles($author, $path, $file)
    {
        $files = Storage::disk('data_folder_local')->get('DMS/'.$this->getAliasFolderbyAuthor($author).'/'.$path .'/'. $file);
        $mime = Storage::disk('data_folder_local')->mimeType('DMS/'.$this->getAliasFolderbyAuthor($author).'/'.$path .'/'. $file);
        return [
            'file' => $files,
            'mime' =>$mime
        ];
    }

    public function uploadFiles($author, $path, $file, $contents)
    {
        return storage::disk('data_folder_local')->put('DMS/'.$this->getAliasFolderbyAuthor($author).'/'.$path.'/'.$file, $contents);
    }

    public function getSizeFiles($author, $path, $file)
    {
        return Storage::disk('data_folder_local')->size('DMS/'.$this->getAliasFolderbyAuthor($author).'/'.$path.'/'.$file);
    }
}
