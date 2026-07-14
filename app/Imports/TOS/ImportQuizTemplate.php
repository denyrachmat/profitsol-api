<?php

namespace App\Imports\TOS;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeSheet;

class ImportQuizTemplate implements ToCollection, WithHeadingRow, WithEvents
{
    public array $parsedData = [];
    private string $sheetTitle = "Untitled Quiz"; // Default backup title

    /**
     * Menggunakan Events untuk mengintip isi cell A1 sebelum data di-parse ke Collection
     */
    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                // Mengambil nilai asli dari cell A1 (Baris 1, Kolom A)
                $cellValue = $event->sheet->getDelegate()->getCell('A1')->getValue();

                if (!empty($cellValue)) {
                    $this->sheetTitle = $cellValue;
                }
            },
        ];
    }

    public function collection(Collection $rows)
    {
        // 1. Inisialisasi struktur utama
        $this->parsedData = [
            "id" => 16,
            "title" => $this->sheetTitle,
            "desc" => "",
            "isQuiz" => "1",
            "forms" => [],
            "exp" => [],
            "ans" => []
        ];

        $currentFormIndex = -1;
        $formIdCounter = 2602;
        $currentFormType = null; // Menyimpan tipe form aktif ('single' atau 'multiple')

        foreach ($rows as $row) {
            $questionText = $row['question'] ?? null;
            $type = $row['type'] ?? null;
            $explanation = $row['explaination'] ?? ''; // Catatan: di Excel-mu tulisannya "Explaination" pake 'i'
            $answerMark = $row['answer_put_1_on_answers'] ?? null; // Heading otomatis slugs dari "Answer (put 1 on answers)"
            $choiceFlag = $row['choice_flag'] ?? null;
            $choiceDesc = $row['choice_desc'] ?? null;

            // 2. Jika mendeteksi ada PERTANYAAN BARU
            if (!empty($questionText)) {
                $currentFormIndex++;
                $currentFormType = trim($type);

                $category = ($currentFormType === 'single') ? 'multiple' : $currentFormType;
                $compType = ($currentFormType === 'single') ? 'multiple-radio' : 'multiple-checkbox';
                $quasarComp = ($currentFormType === 'single') ? 'q-radio' : 'q-checkbox';

                $this->parsedData['forms'][$currentFormIndex] = [
                    "id" => '',
                    "type" => "form",
                    "required" => false,
                    "seq_name" => 1,
                    "content" => [
                        "component" => [
                            "label" => "Multiple Choice",
                            "category" => $category,
                            "value" => [
                                "type" => $compType,
                                "comp" => $quasarComp
                            ]
                        ],
                        "detail_data" => [],
                        "label" => ($currentFormIndex + 1) . ".\t" . $questionText
                    ],
                    "logics" => []
                ];

                // Kunci pengisian Explanation hanya di baris pertanyaan utama agar tidak ketimpa
                $this->parsedData['exp'][$currentFormIndex] = trim($explanation);

                // Inisialisasi awal key ans untuk index ini
                $this->parsedData['ans'][$currentFormIndex] = null;
            }

            // 3. PROSES PENGISIAN OPTIONS & DETEKSI JAWABAN (Berjalan di setiap baris pilihan A, B, C, D)
            if (!empty($choiceFlag) && $currentFormIndex >= 0) {
                $flag = trim($choiceFlag); // Berisi 'A', 'B', 'C', atau 'D' dari kolom E
                $uniqueOptId = 'opt-' . rand(8000, 8999);
                $formattedLabel = $flag . ".\t" . trim($choiceDesc);

                // MASUKKAN LANGSUNG HURUFNJA DARI KOLOM E KE PROPERTI VALUE:
                $this->parsedData['forms'][$currentFormIndex]['content']['detail_data'][] = [
                    "col_det_id" => $uniqueOptId,
                    "col_det_label" => "",
                    "value" => $flag, // <--- FIX DI SINI: Langsung pakai huruf asli ('A', 'B', dll), hapus $numericValue
                    "label" => $formattedLabel
                ];

                // --- LOGIC DETEKSI KUNCI JAWABAN BERDASARKAN ANGKA 1 ---
                if (trim($answerMark) == '1') {
                    if ($currentFormType === 'multiple') {
                        if (!is_array($this->parsedData['ans'][$currentFormIndex])) {
                            $this->parsedData['ans'][$currentFormIndex] = [];
                        }
                        $this->parsedData['ans'][$currentFormIndex][] = $flag;
                    } elseif ($currentFormType === 'single') {
                        if (is_null($this->parsedData['ans'][$currentFormIndex])) {
                            $this->parsedData['ans'][$currentFormIndex] = $flag;
                        }
                    } else {
                        $this->parsedData['ans'][$currentFormIndex] = $flag;
                    }
                }
            }
        }

        // Normalisasi: Pastikan jika ada soal yang tipenya single/string biasa tapi bernilai null (karena tidak diisi 1), jadikan string kosong
        foreach ($this->parsedData['ans'] as $index => $value) {
            if (is_null($value)) {
                $this->parsedData['ans'][$index] = "";
            }
        }
    }

    /**
     * Karena baris 1 (A1) dipakai untuk Title, pastikan header row 
     * untuk mapping ['question', 'type', etc] berada di baris ke-2 (index 2).
     */
    public function headingRow(): int
    {
        return 2;
    }
}
