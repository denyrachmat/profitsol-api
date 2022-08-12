<?php

namespace App\Traits\DMS;

use App\Models\DMS\DMSFolderMstr;
use App\Models\DMS\DMSDocMstr;
use App\Models\DMS\DMSFolderRootMstr;

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
}
