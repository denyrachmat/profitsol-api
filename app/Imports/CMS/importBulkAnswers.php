<?php

namespace App\Imports\CMS;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas; // Tambahkan ini
use PhpOffice\PhpSpreadsheet\Shared\Date; // Tambahkan ini
use Illuminate\Http\Request;
use App\Models\MRS\MRSReportColsDet;
use App\Http\Controllers\API\CMS\FormController;

// Tambahkan implements WithCalculatedFormulas
class importBulkAnswers implements ToCollection, WithCalculatedFormulas
{
    private $username, $id, $formID, $setup;

    public function __construct($username, $id, $formID, $setup = null)
    {
        $this->username = $username;
        $this->id = $id;
        $this->formID = $formID;
        $this->setup = $setup;
    }

    public function collection(Collection $collection)
    {
        $getData = MRSReportColsDet::where('mrm_id', $this->id)
            ->where('mrcd_isActive', 1)
            ->where('mrcd_isExported', 1)
            ->where('mrcd_col_prop', 'cols')
            ->get()
            ->toArray();

        $listIDCols = [];
        foreach ($collection as $key => $collect) {
            if ($key === 2) {
                $listIDCols = $collect->toArray();
            }

            if ($key > 3) {
                $answers = [];
                $keyAns = 0;
                $batchID = null;
                foreach ($getData as $keyCols => $valueCols) {
                    $index = array_search($valueCols['id'], $listIDCols);
                    
                    if ($index !== false && isset($collect[$index])) {
                        // Nilai di sini sudah otomatis terhitung jika hasil formula 
                        // karena sudah menggunakan WithCalculatedFormulas
                        $cellValue = $this->transformValue($collect[$index]);

                        if (!empty($cellValue)) {
                            $splitField = explode('_', $valueCols['mrcd_field']);
                            $getCMSKeys = $splitField[count($splitField) - 1];

                            if ($this->setup && isset($this->setup['bulkKeys']) && count($this->setup['bulkKeys']) > 0) {
                                # code...
                            }

                            $answers[$keyAns][$getCMSKeys] = $cellValue;
                            $keyAns++;
                        }
                    }
                }

                if (!empty($answers)) {
                    $result = (new FormController())->storeAnswers(new Request([
                        'username' => $this->username,
                        'id'       => $this->formID,
                        'ans'      => $answers,
                    ]));
                    
                    logger('Result row ' . $key . ': ' . json_encode($result));
                }
            }
        }
    }

    /**
     * Helper untuk menangani konversi tanggal excel atau nilai lainnya
     */
    private function transformValue($value)
    {
        // 1. Cek jika ini adalah angka yang kemungkinan besar adalah format tanggal Excel
        // Excel date biasanya antara 10000 (tahun 1927) sampai 90000 (tahun 2146)
        if (is_numeric($value) && $value > 30000 && $value < 60000) {
            try {
                // Menggunakan library bawaan untuk akurasi tinggi
                return Date::excelToDateTimeObject($value)->format('Y-m-d');
            } catch (\Exception $e) {
                return $value;
            }
        }

        return $value;
    }
}