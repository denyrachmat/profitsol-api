<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;

class SpreadsheetReaderService
{
    /**
     * Read a spreadsheet into an array of sheets, each a 2D array of cells.
     *
     * Legacy BIFF2 ("Excel 2.0") .xls files are read by a small built-in parser
     * because PhpSpreadsheet / Maatwebsite Excel cannot identify or read them.
     * Everything else is delegated to Maatwebsite Excel.
     *
     * @return array<int, array<int, array<int, mixed>>>
     */
    public function toArray(string $filePath): array
    {
        if ($this->isBiff2($filePath)) {
            return [$this->readBiff2($filePath)];
        }

        return Excel::toArray(new \stdClass, $filePath);
    }

    /**
     * Legacy BIFF2 workbooks start with a BOF record (type 0x0009), i.e. the
     * bytes 09 00 — unlike modern .xls which are OLE compound files starting
     * with D0 CF 11 E0.
     */
    public function isBiff2(string $filePath): bool
    {
        $handle = @fopen($filePath, 'rb');
        if ($handle === false) {
            return false;
        }

        $head = fread($handle, 2);
        fclose($handle);

        return $head === "\x09\x00";
    }

    /**
     * Minimal BIFF2 reader covering the records used by these legacy exports:
     * BOF (0x0009), EOF (0x000A), LABEL (0x0004) and NUMBER (0x0003).
     *
     * Record layout (little-endian):
     *   LABEL : row(2) col(2) xf(2) attr(1) len(1) string(len)
     *   NUMBER: row(2) col(2) xf(2) attr(1) double(8)
     *
     * @return array<int, array<int, mixed>> [row][col] => value
     */
    private function readBiff2(string $filePath): array
    {
        $data = file_get_contents($filePath);
        $length = strlen($data);
        $offset = 0;

        $cells = [];
        $maxRow = -1;
        $maxCol = -1;

        while ($offset + 4 <= $length) {
            $type = unpack('v', substr($data, $offset, 2))[1];
            $recordLength = unpack('v', substr($data, $offset + 2, 2))[1];
            $payload = substr($data, $offset + 4, $recordLength);
            $offset += 4 + $recordLength;

            if ($type === 0x000A) { // EOF
                break;
            }

            if ($type === 0x0004 && $recordLength >= 8) { // LABEL (string cell)
                $row = unpack('v', substr($payload, 0, 2))[1];
                $col = unpack('v', substr($payload, 2, 2))[1];
                $stringLength = ord($payload[7]);
                $cells[$row][$col] = substr($payload, 8, $stringLength);
            } elseif ($type === 0x0003 && $recordLength >= 15) { // NUMBER cell
                $row = unpack('v', substr($payload, 0, 2))[1];
                $col = unpack('v', substr($payload, 2, 2))[1];
                $cells[$row][$col] = unpack('d', substr($payload, $recordLength - 8, 8))[1];
            } else {
                continue;
            }

            $maxRow = max($maxRow, $row);
            $maxCol = max($maxCol, $col);
        }

        $grid = [];
        for ($row = 0; $row <= $maxRow; $row++) {
            $gridRow = [];
            for ($col = 0; $col <= $maxCol; $col++) {
                $gridRow[$col] = $cells[$row][$col] ?? null;
            }
            $grid[$row] = $gridRow;
        }

        return $grid;
    }
}
