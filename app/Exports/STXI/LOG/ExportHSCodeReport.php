<?php

namespace App\Exports\STXI\LOG;

use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Facades\DB;
use App\Models\STXI\LOG\HSCodeGroupBeaDetail;
use App\Models\STXI\LOG\INSWDataDocBeaMaster;
use App\Models\STXI\LOG\HSCodeUplMaster;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExportHSCodeReport implements FromCollection,WithHeadings
{
    public function startRow(): int
    {
        return 2;
    }

    public function headings(): array
    {
        $firstPart = [
            'Part Code',
            'BG',
            'Biz Unit',
            'Item Desc',
            'QC Desc',
            'Maker PN',
            'Maker Name',
            'Series',
            'Maker Recomendation',
            'QC Doc',
            'Approval Date',
            'HS Code',
            'Section',
            'Tarif (%)',
            'PPN (%)',
            'PPH (%)',
            'PPnBM (%)',
            'Cukai (%)',
            'UoM'
        ];

        $getBCData = INSWDataDocBeaMaster::whereNotIn('ZIDBD_DOCCD', [611,632])->get();
        $cols1 = [
            'TATANIAGA BORDER'
        ];

        $cols2 = [
            'TATANIAGA POST BORDER'
        ];

        for ($i=0; $i < count($getBCData) - 1 ; $i++) {
            $cols1[] = '';
            $cols2[] = '';
        }

        $firstPartEmpty = [];
        for ($j=0; $j < count($firstPart); $j++) {
            $firstPartEmpty[] = '';
        }

        $hasil[] = array_merge(
            $firstPart,
            $cols1,
            $cols2,
            [
                'KUMHS',
                'Catatan BAB',
                'Explanatory Note'
            ]
        );

        $listPLB = ['16', '28'];
        $listGenImport = ['20'];
        $listTPB = ['23','25'];
        $listFTZ = ['511','513'];

        $colsDet1 = [];
        for ($i=0; $i < count($listPLB); $i++) {
            $colsDet1[] = $i === 0 ? 'TPB' : '';
        }

        $colsDet2 = [];
        for ($i=0; $i < count($listGenImport); $i++) {
            $colsDet2[] = $i === 0 ? 'General Import' : '';
        }

        $colsDet3 = [];
        for ($i=0; $i < count($listTPB); $i++) {
            $colsDet3[] = $i === 0 ? 'TPB' : '';
        }

        $colsDet4 = [];
        for ($i=0; $i < count($listFTZ); $i++) {
            $colsDet4[] = $i === 0 ? 'FTZ' : '';
        }

        $colsComb = array_merge($colsDet1, $colsDet2, $colsDet3, $colsDet4);

        $hasil[] = array_merge(
            $firstPartEmpty,
            $colsComb,
            $colsComb
        );

        $hasil[] = array_merge(
            $firstPartEmpty,
            (clone $getBCData)->pluck('ZIDBD_DOCNM')->toArray(),
            (clone $getBCData)->pluck('ZIDBD_DOCNM')->toArray(),
        );

        return $hasil;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $data = DB::connection('sqlsrv_log')->table('V_HSCODE_SYS')->get();
        $hasil = [];

        foreach ($data as $key => $value) {
            $hasil[] = $value;
        }
        return collect($hasil);
    }

    public function headerGroupRecurs($initData, $initHeader, $submitedData = []) {
        if (count($submitedData) === 0) {
            $submitedData[] = $initHeader;
        }

        $nowData = current($initData);
        if (count($nowData['child_group']) > 0) {
            $submitedData[count($submitedData) - 1] = array_merge();
        }
    }
}
