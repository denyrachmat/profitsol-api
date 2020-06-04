<?php

namespace App\Models\PORTAL\DOCCREATOR;

use Illuminate\Database\Eloquent\Model;

class ContentCreator extends Model
{
    protected $table = 'portal_content_creator';

    protected $fillable = [
        'content_var_id',
        'content_var_value',
        'content_users',
        'content_creator_id',
    ];
}
