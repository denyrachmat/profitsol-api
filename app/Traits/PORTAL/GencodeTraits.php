<?php

namespace App\Traits\PORTAL;

use Illuminate\Support\Facades\DB;
use App\Models\PORTAL\PortalGencode;
use Illuminate\Http\Request;

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
                    $gencode->whereRaw("CAST($key AS varchar(max)) = ?", [$value]);
                }
            }

            if ($withParents) {
                if ($forceShowAll) {
                    $gencode->with(['children' => function($query) {
                        $query->limit(1000); // Prevent infinite recursion
                    }]);
                } else {
                    $gencode->with(['children' => function($query) {
                        $query->limit(1000); // Prevent infinite recursion
                    }])->whereNull('pgm_parent');
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
                                    if (isset($splitTypeString[2]) && $splitTypeString[2] === 'grouped') {

                                        // Build/append grouped array for dynamic key ($keysCheck) and merge rows with same other fields
                                        $currentVal = $value[$selectStr];

                                        // Try to find an existing row in $hasil that matches all already-set fields (except the grouped field)
                                        $existingIndex = null;
                                        $compareFields = $hasil[$key] ?? [];
                                        if (!empty($compareFields)) {
                                            // Do not compare the grouped field itself
                                            if (array_key_exists($keysCheck, $compareFields)) {
                                                unset($compareFields[$keysCheck]);
                                            }
                                            // Optional: ignore 'order' when grouping
                                            if (array_key_exists('order', $compareFields)) {
                                                unset($compareFields['order']);
                                            }

                                            if (!empty($compareFields)) {
                                                foreach ($hasil as $idx => $rowCandidate) {
                                                    if ($idx === $key) {
                                                        continue;
                                                    }
                                                    $match = true;
                                                    foreach ($compareFields as $ck => $cv) {
                                                        if (!array_key_exists($ck, $rowCandidate) || $rowCandidate[$ck] !== $cv) {
                                                            $match = false;
                                                            break;
                                                        }
                                                    }
                                                    if ($match) {
                                                        $existingIndex = $idx;
                                                        break;
                                                    }
                                                }
                                            }
                                        }

                                        // Determine where to place the grouped values
                                        $targetIndex = $existingIndex !== null ? $existingIndex : $key;

                                        // Ensure target has an array for the grouped key
                                        if (!isset($hasil[$targetIndex][$keysCheck]) || !is_array($hasil[$targetIndex][$keysCheck])) {
                                            $hasil[$targetIndex][$keysCheck] = [];
                                        }

                                        // Append unique value
                                        if ($currentVal !== null && $currentVal !== '') {
                                            if (!in_array($currentVal, $hasil[$targetIndex][$keysCheck], true)) {
                                                $hasil[$targetIndex][$keysCheck][] = $currentVal;
                                            }
                                        }

                                        // If merged into an existing row, remove the current row to avoid duplicates.
                                        // Note: for reliable behavior, ensure the grouped field is processed last in $selectAs.
                                        if ($existingIndex !== null && $targetIndex !== $key) {
                                            unset($hasil[$key]);
                                        }
                                    } else {
                                        // Just convert to array with single value
                                        $hasil[$key][$keysCheck] = json_decode($value[$selectStr], true);
                                    }
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
        // Extract all key fields including store_separately ones for conditions
        $allKeyFields = [];
        foreach ($keys as $key => $value) {
            if (is_array($value) && isset($value['store_separately']) && $value['store_separately'] === true) {
                // For store_separately fields, we need to include them in conditions
                $allKeyFields[$key] = $value;
            } else {
                $allKeyFields[$key] = $value;
            }
        }
        // Delete existing records that match base keys before creating new ones
        if (!empty($allKeyFields)) {
            $deleteConditions = [];
            foreach ($allKeyFields as $key => $value) {
                if (is_array($value) && isset($value['store_separately']) && $value['store_separately'] === true) {
                    // For store_separately fields, include them in delete conditions
                    if (isset($data[$key]) && is_array($data[$key])) {
                        // If it's an array with store_separately, we need to delete all combinations
                        // So we include the field but will use whereIn for arrays
                        $deleteConditions[$key] = $data[$key]['value'] ?? $data[$key];
                    }
                } else {
                    // Use the key from $keys as condition
                    if (isset($data[$key])) {
                        $deleteConditions[$key] = is_array($data[$key]) ? json_encode($data[$key]) : $data[$key];
                    }
                }
            }

            logger()->info('Deleting existing Gencode with conditions: ' . json_encode($deleteConditions));

            if (!empty($deleteConditions)) {
                $query = PortalGencode::query();
                foreach ($deleteConditions as $field => $value) {
                    if (is_array($value)) {
                        // Use whereIn for array values
                        $query->whereIn($field, $value);
                    } else {
                        $query->where($field, $value);
                    }
                }
                $query->delete();
            }
        }
        // Update keys array to use processed keys
        $keys = $allKeyFields;

        // Identify fields with store_separately
        $separateFields = [];
        $baseData = [];

        foreach ($data as $key => $value) {
            if (is_array($value) && isset($value['store_separately']) && $value['store_separately'] === true) {
                $separateFields[$key] = $value['value'] ?? [];
            } else {
                if (is_array($value)) {
                    $baseData[$key] = json_encode($value);
                } else {
                    $baseData[$key] = $value;
                }
            }
        }

        // If there are fields to store separately, create combinations
        if (!empty($separateFields)) {
            $fieldNames = array_keys($separateFields);
            $fieldValues = array_values($separateFields);

            // Generate all combinations (Cartesian product)
            $combinations = [[]];
            foreach ($fieldValues as $values) {
                $append = [];
                foreach ($combinations as $combination) {
                    foreach ($values as $value) {
                        $append[] = array_merge($combination, [$value]);
                    }
                }
                $combinations = $append;
            }

            // Create records for each combination
            foreach ($combinations as $combination) {
                $recordData = $baseData;

                // Add each separate field value
                foreach ($fieldNames as $idx => $fieldName) {
                    $recordData[$fieldName] = $combination[$idx];
                }

                // Build conditions for updateOrCreate
                if (!empty($keys)) {
                    $conditions = [];
                    foreach ($keys as $keyField => $keyValue) {
                        if (is_array($keyValue) && isset($keyValue['store_separately'])) {
                            // Use the current iteration value
                            if (isset($recordData[$keyField])) {
                                $conditions[$keyField] = $recordData[$keyField];
                            }
                        } elseif (isset($recordData[$keyField])) {
                            $conditions[$keyField] = $recordData[$keyField];
                        }
                    }

                    if (!empty($conditions)) {
                        logger()->info('Updating or creating Gencode with conditions: ' . json_encode($conditions) . ' and data: ' . json_encode($recordData));
                        PortalGencode::updateOrCreate($conditions, $recordData);
                    }
                } else {
                    PortalGencode::create($recordData);
                }
            }

            return response()->json(['success' => true]);
        }

        // No separate fields, process normally
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

        return PortalGencode::create($baseData);
    }
}