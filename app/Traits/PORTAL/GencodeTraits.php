<?php

namespace App\Traits\PORTAL;

use Illuminate\Support\Facades\DB;
use App\Models\PORTAL\PortalGencode;
use Illuminate\Http\Request;
use App\Notifications\PORTAL\PortalEmailNotification;
use Illuminate\Support\Facades\Notification;
use App\Models\User;

trait GencodeTraits
{
    public function getDataGencode(
        $id,
        $filter = [],
        $selectAs = [],
        $orderBy = [],
        $firstSelect = false,
        $withParents = false,
        $forceShowAll = false,
        $groupBy = [],
        $data = []
    ) {
        if (count($data) > 0) {
            $hasilnya = $data;
        } else {
            $gencode = PortalGencode::where('pgm_code', $id);

            if (!empty($filter)) {
                foreach ($filter as $key => $value) {
                    if (is_array($value)) {
                        $gencode->whereIn(DB::raw("CAST($key AS varchar(max))"), $value);
                    } else {
                        $gencode->whereRaw("CAST($key AS varchar(max)) = ?", [$value]);
                    }
                }
            }

            if ($withParents) {
                if ($forceShowAll) {
                    $gencode->with([
                        'children' => function ($query) {
                            $query->limit(1000); // Prevent infinite recursion
                        }
                    ]);
                } else {
                    $gencode->with([
                        'children' => function ($query) {
                            $query->limit(1000); // Prevent infinite recursion
                        }
                    ])->whereNull('pgm_parent');
                }
            }

            if (count($orderBy) > 0) {
                foreach ($orderBy as $key => $value) {
                    $gencode->orderBy($key, $value);
                }
            }

            $hasilnya = $gencode->get()->toArray();
        }

        if (!empty($selectAs) && count($selectAs) > 0) {
            $hasil = [];
            $keyOrderForms = 1;
            foreach ($hasilnya as $key => $value) {
                foreach ($selectAs as $keySel => $valueSel) {
                    $splitTypeString = explode('|', $valueSel);

                    // Check if there's a :max or :min modifier
                    if (count($splitTypeString) > 1) {
                        if (strpos($splitTypeString[1], ':max') !== false) {
                            // Find max value for this field across all items
                            $maxValue = null;
                            foreach ($hasilnya as $item) {
                                $currentValue = $item[$splitTypeString[0]] ?? null;
                                if ($currentValue !== null) {
                                    if ($maxValue === null || $currentValue > $maxValue) {
                                        $maxValue = $currentValue;
                                    }
                                }
                            }
                            // Override current value with max
                            if ($maxValue !== null) {
                                $value[$splitTypeString[0]] = $maxValue;
                            }
                        } elseif (strpos($splitTypeString[1], ':min') !== false) {
                            // Find min value for this field across all items
                            $minValue = null;
                            foreach ($hasilnya as $item) {
                                $currentValue = $item[$splitTypeString[0]] ?? null;
                                if ($currentValue !== null) {
                                    if ($minValue === null || $currentValue < $minValue) {
                                        $minValue = $currentValue;
                                    }
                                }
                            }
                            // Override current value with min
                            if ($minValue !== null) {
                                $value[$splitTypeString[0]] = $minValue;
                            }
                        }
                    }

                    $selectStr = $splitTypeString[0];

                    $keysCheck = $value[$keySel] ?? $keySel;
                    if ($firstSelect) {
                        $hasil[$keysCheck] = (string) $value[$selectStr];
                        if (count($splitTypeString) > 1) {
                            if ($splitTypeString[1] === 'int') {
                                $hasil[$keysCheck] = (int) $value[$selectStr];
                            } elseif ($splitTypeString[1] === 'bool') {
                                $hasil[$keysCheck] = (bool) $value[$selectStr];
                            } else {
                                $hasil[$keysCheck] = (string) $value[$selectStr];
                            }
                        }
                    } else {
                        if (is_array($value[$selectStr])) {
                            // Recursively process array values
                            if (count($value[$selectStr]) > 0) {
                                $hasil[$key][$keySel] = $this->getDataGencode(
                                    $value['pgm_code'],
                                    $filter,
                                    $selectAs,
                                    $orderBy,
                                    $firstSelect,
                                    $withParents,
                                    $forceShowAll,
                                    $groupBy,
                                    $value[$selectStr]
                                );
                            }
                        } elseif (!is_array($value[$selectStr])) {
                            $hasil[$key][$keysCheck] = (string) $value[$selectStr];
                            if (count($splitTypeString) > 1) {
                                if ($splitTypeString[1] === 'int') {
                                    $hasil[$key][$keysCheck] = (int) $value[$selectStr];
                                } elseif ($splitTypeString[1] === 'bool') {
                                    $hasil[$key][$keysCheck] = (bool) $value[$selectStr];
                                } elseif ($splitTypeString[1] === 'array') {
                                    // sementara set value single dulu (akan digroup di akhir bila grouped)
                                    $hasil[$key][$keysCheck] = $value[$selectStr];
                                } else {
                                    $hasil[$key][$keysCheck] = (string) $value[$selectStr];
                                }
                            }
                        }
                    }

                    if ($keySel === 'order') {
                        if (empty($value[$keySel])) {
                            $hasil[$key][$keySel] = $keyOrderForms++;
                        } else {
                            $hasil[$key][$keySel] = (int) $value[$keySel];
                        }
                    }
                }
            }

            // =========================
            // NEW GROUPED ARRAY HANDLER
            // =========================

            // cari field mana aja yang bertipe array|grouped
            $groupedFields = [];
            $normalFields = [];

            foreach ($selectAs as $alias => $spec) {
                $parts = explode('|', $spec);
                $type = $parts[1] ?? 'string';
                $isGrouped = ($type === 'array' && ($parts[2] ?? null) === 'grouped');

                if ($isGrouped) {
                    $groupedFields[] = $alias;   // alias output, misal 'category', 'email'
                } else {
                    $normalFields[] = $alias;    // field lain jadi signature grouping
                }
            }

            if (!empty($groupedFields)) {
                $tmp = [];

                foreach ($hasil as $row) {
                    // build signature dari normal fields
                    $sigParts = [];
                    foreach ($normalFields as $nf) {
                        $sigParts[$nf] = $row[$nf] ?? null;
                    }
                    $sigKey = md5(json_encode($sigParts));

                    if (!isset($tmp[$sigKey])) {
                        // init row baru
                        $tmp[$sigKey] = $sigParts;

                        // init grouped fields sebagai array kosong
                        foreach ($groupedFields as $gf) {
                            $tmp[$sigKey][$gf] = [];
                        }
                    }

                    // append grouped values
                    foreach ($groupedFields as $gf) {
                        $val = $row[$gf] ?? null;
                        if ($val !== null && $val !== '') {
                            if (!in_array($val, $tmp[$sigKey][$gf], true)) {
                                $tmp[$sigKey][$gf][] = $val;
                            }
                        }
                    }
                }

                // overwrite hasil jadi versi grouped
                $hasil = array_values($tmp);
            }

            // =========================
            // END NEW GROUPED HANDLER
            // =========================

            if (count($groupBy) > 0) {
                $grouped = [];
                foreach ($hasil as $item) {
                    $groupKey = '';
                    foreach ($groupBy as $groupField) {
                        $groupKey .= ($item[$groupField] ?? '') . '_';
                    }
                    $groupKey = rtrim($groupKey, '_');

                    if (!isset($grouped[$groupKey])) {
                        $grouped[$groupKey] = $item;
                    }
                }

                return array_values($grouped);
            }

            return $hasil;
        } else {
            return $firstSelect ? $hasilnya[0] : $hasilnya;
        }
    }

    public function isGencodeExists($id, $filter = [])
    {
        $gencode = PortalGencode::where('pgm_code', $id);

        if (!empty($filter)) {
            foreach ($filter as $key => $value) {
                $gencode->whereRaw("CAST($key AS nvarchar(max)) = ?", [$value]);
            }
        }

        return $gencode->exists();
    }

    public function saveGencode(Request $request)
    {
        $data = $request->input('data', []);
        $keys = $request->input('keys', []);
        $notify = $request->input('notify', []);

        /**
         * 1) Ambil semua key fields yang store_separately=true
         *    Ini dipakai buat kondisi delete & updateOrCreate
         */
        $allKeyFields = [];
        foreach ($keys as $key => $value) {
            if (is_array($value) && ($value['store_separately'] ?? false) === true) {
                $allKeyFields[$key] = $value;
            }
        }

        /**
         * 2) Delete existing records yang match base keys
         */
        if (!empty($allKeyFields)) {
            $deleteConditions = [];

            foreach ($allKeyFields as $key => $value) {
                if (($value['store_separately'] ?? false) === true) {
                    if (isset($data[$key]) && is_array($data[$key])) {
                        // ambil array values
                        $deleteConditions[$key] = $data[$key]['value'] ?? $data[$key];
                    }
                } else {
                    if (isset($data[$key])) {
                        $deleteConditions[$key] = is_array($data[$key])
                            ? json_encode($data[$key])
                            : $data[$key];
                    }
                }
            }

            logger()->info('Deleting existing Gencode with conditions: ' . json_encode($deleteConditions));

            if (!empty($deleteConditions)) {
                $query = PortalGencode::query();
                foreach ($deleteConditions as $field => $value) {
                    if (is_array($value)) {
                        $query->whereIn($field, $value);
                    } else {
                        $query->where($field, $value);
                    }
                }
                $query->delete();
            }
        }

        /**
         * 3) Update keys jadi cuma yang store_separately=true
         */
        $keys = $allKeyFields;

        /**
         * 4) Pisahkan data:
         *    - $separateFields = field store_separately
         *    - $baseData = field biasa
         */
        $separateFields = [];
        $baseData = [];

        foreach ($data as $key => $value) {
            if (is_array($value) && ($value['store_separately'] ?? false) === true) {
                $separateFields[$key] = $value['value'] ?? [];
            } else {
                $baseData[$key] = is_array($value) ? json_encode($value) : $value;
            }
        }

        /**
         * 5) Kalau ada separateFields -> bikin kombinasi cartesian
         */
        if (!empty($separateFields)) {

            // Pivot selalu pgm_value
            $pivotValues = $separateFields['pgm_value'] ?? [];

            // Other separate fields (boleh kosong / beda panjang)
            $v2 = $separateFields['pgm_value2'] ?? [];
            $v3 = $separateFields['pgm_value3'] ?? [];

            // Field order FIX sesuai kolom tabel
            $fieldNames = array_values(array_filter(
                ['pgm_value', 'pgm_value2', 'pgm_value3'],
                fn($f) => array_key_exists($f, $separateFields)
            ));

            // Build combinations: pivot x v2 x v3 (cartesian)
            $combinations = [];

            if (!empty($pivotValues)) {
                foreach ($pivotValues as $pv) {

                    // kalau kosong, biar tetep 1 variasi null
                    $v2List = !empty($v2) ? $v2 : [null];
                    $v3List = !empty($v3) ? $v3 : [null];

                    foreach ($v2List as $val2) {
                        foreach ($v3List as $val3) {
                            // urutan harus match fieldNames
                            $combinations[] = [$pv, $val2, $val3];
                        }
                    }
                }
            }

            logger()->info('Creating Gencode combinations (cartesian pivot pgm_value): ' . json_encode($combinations));

            /**
             * 6) Insert / update record per kombinasi
             */
            foreach ($combinations as $combination) {
                $recordData = $baseData;

                // isi field separate sesuai urutan fixed fieldNames
                foreach ($fieldNames as $idx => $fieldName) {
                    $recordData[$fieldName] = $combination[$idx] ?? null;
                }

                // Build conditions untuk updateOrCreate
                if (!empty($keys)) {
                    $conditions = [];
                    foreach ($keys as $keyField => $keyValue) {
                        if (($keyValue['store_separately'] ?? false) === true) {
                            if (isset($recordData[$keyField])) {
                                $conditions[$keyField] = $recordData[$keyField];
                            }
                        } elseif (isset($recordData[$keyField])) {
                            $conditions[$keyField] = $recordData[$keyField];
                        }
                    }

                    if (!empty($conditions)) {
                        PortalGencode::updateOrCreate($conditions, $recordData);
                    } else {
                        PortalGencode::create($recordData);
                    }
                } else {
                    PortalGencode::create($recordData);
                }
            }

            return response()->json(['success' => true]);
        }

        /**
         * 7) Kalau gak ada separateFields -> normal insert/update
         */
        if (!empty($keys)) {
            $conditions = [];
            foreach ($keys as $key => $value) {
                if (isset($baseData[$key])) {
                    $conditions[$key] = $baseData[$key];
                }
            }

            if (!empty($conditions)) {
                return PortalGencode::updateOrCreate($conditions, $baseData);
            }
        }

        if (!empty($notify)) {
            // Kirim notifikasi
            $subject = $notify['title'] ?? 'Notification';
            $content = $notify['message'] ?? '';
            $fromDesc = config('app.name');
            $linkPost = $notify['link'] ? env('FE_URL').$notify['link'] : env('FE_URL');
            $toUser = $notify['to'] ?? null;
            $sentMode = $notify['methods'] ?? ['email', 'webpush'];

            if (is_array($toUser)) {
                $users = User::whereIn('username', $toUser)->with('det')->get();
            } else {
                $users = User::where('username', $toUser)->with('det')->get();
            }

            foreach ($users as $keyUser => $valueUsers) {
                $notification = new PortalEmailNotification(
                    $subject,
                    $content,
                    $fromDesc,
                    $linkPost,
                    $valueUsers,
                    $sentMode
                );

                if ($toUser) {
                    // Kirim ke user spesifik
                    Notification::route('mail', $valueUsers->username)->notify($notification);
                }
            }
        }

        return PortalGencode::create($baseData);
    }

    public function deleteGencode(Request $request)
    {
        $id = $request->input('id', null);
        $filter = $request->input('filter', []);

        $gencode = PortalGencode::where('pgm_code', $id);

        if (!empty($filter)) {
            foreach ($filter as $key => $value) {
                $gencode->whereRaw("CAST($key AS nvarchar(max)) = ?", [$value]);
            }
        }

        $deletedCount = $gencode->delete();

        return response()->json([
            'success' => true,
            'deleted_count' => $deletedCount,
        ]);
    }
}