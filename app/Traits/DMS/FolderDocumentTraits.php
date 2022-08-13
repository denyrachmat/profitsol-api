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
        return Storage::disk('data_folder')->makeDirectory('DMS/'.$this->getAliasFolderbyAuthor($author).'/'.$path);
    }

    public function deleteFolder($author, $path)
    {
        return Storage::disk('data_folder')->deleteDirectory('DMS/'.$this->getAliasFolderbyAuthor($author).'/'.$path);
    }
}
