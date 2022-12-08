<?php

namespace App\Http\Controllers\STXI\LOG;

use App\Http\Controllers\API\PORTAL\BaseController;
use Illuminate\Http\Request;

class INSWDataController extends BaseController
{
    public function getData($hsCode)
    {
        $endpoint = 'https://api.insw.go.id/api-prod-ba/cms/hscode?keyword='.$hsCode.'&size=200&from=0';

        $content = [];
        $guzz = new \GuzzleHttp\Client([
            'headers' => [
                'Authorization' => 'Basic aW5zd18yOmJhYzJiYXM2'
            ]
        ]);

        $res = $guzz->request('GET', $endpoint);

        $content['CURL'] = json_decode($res->getBody(), true);

        return $content['CURL'];
    }
}
