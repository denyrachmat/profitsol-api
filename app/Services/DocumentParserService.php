<?php

namespace App\Services;

use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser;

class DocumentParserService
{
    public function extractText(string $filePath, string $extension): string
    {
        if ($extension === 'docx') {
            return $this->readWord($filePath);
        } elseif ($extension === 'pdf') {
            return $this->readPdf($filePath);
        }
        
        throw new \Exception("Format file tidak didukung.");
    }

    private function readWord($filePath): string
    {
        $phpWord = IOFactory::load($filePath);
        $text = '';
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText() . "\n";
                }
            }
        }
        return $text;
    }

    private function readPdf($filePath): string
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        return $pdf->getText();
    }
}