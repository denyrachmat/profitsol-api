<?php

namespace App\Models\PORTAL\CUSTOMS;

use Illuminate\Database\Eloquent\Model;

class CircularTen extends Model
{
    protected $table = 'portal_custom_cirten';

    protected $fillable = [
        'creator_id',
        'doc_id'
    ];
}
