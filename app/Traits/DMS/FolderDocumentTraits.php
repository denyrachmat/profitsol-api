<?php

namespace App\Traits\DMS;

use App\Models\DMS\DMSFolderMstr;

trait FolderDocumentTraits
{
    public function getFolder($author, $id = null)
    {
        $data = DMSFolderMstr::with('childFolders')->where('p_u_username' ,$author)->whereNull('dfm_parent_id');

        return !empty($id) ? $data->where('id', $id)->get() : $data->get();
    }
}
