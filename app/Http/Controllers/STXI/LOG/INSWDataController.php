<?php

namespace App\Http\Controllers\STXI\LOG;

use App\Http\Controllers\API\PORTAL\BaseController;
use Illuminate\Http\Request;
use App\Jobs\STXI\LOG\SyncINSWRules;
use App\Traits\STXI\LOG\INSWTraits;
use App\Jobs\STXI\LOG\SyncINSWHeader;
class INSWDataController extends BaseController
{
    use INSWTraits;

    public function syncINSWData($hsCode = '')
    {
        SyncINSWRules::dispatch($hsCode)->onQueue('INSWQueueRunning');

        return 'Checking INSW Rules has been started';
    }

    public function syncINSWDirectHeader($hsCode = '')
    {
        SyncINSWHeader::dispatch($hsCode)->onQueue('INSWQueueRunning');

        return 'Checking INSW Header has been started';
    }
}
