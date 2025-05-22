<?php

namespace App\Traits\PORTAL;

use Illuminate\Support\Facades\DB;
use App\Models\PORTAL\PortalGencode;

trait GencodeTraits
{
    public function getDataGencode($id, $filter = [], $selectAs = [])
    {
        $gencode = PortalGencode::where('pgm_code', $id);

        if (!empty($filter)) {
            foreach ($filter as $key => $value) {
                $gencode->where($key, $value);
            }
        }


        if (!empty($selectAs)) {
            $hasil = [];
            foreach ($gencode->get() as $key => $value) {
                foreach ($selectAs as $keySel => $valueSel) {
                    $splitTypeString = explode('|', $valueSel);
                    $selectStr = $splitTypeString[0];

                    $hasil[$value[$keySel]] = $value[$selectStr];
                    if (count($splitTypeString) > 1) {
                        if ($splitTypeString[1] === 'int') {
                            $hasil[$value[$keySel]] = (int)$value[$selectStr];
                        } elseif ($splitTypeString[1] === 'bool') {
                            $hasil[$value[$keySel]] = (bool)$value[$selectStr];
                        }
                    }
                }
            }
        } else {
            $hasil = $gencode->get();
        }

        return $hasil;
    }
}
