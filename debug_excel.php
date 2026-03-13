<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$path = 'export_sipw.xlsx';

if (!file_exists($path)) {
    echo "File not found: $path\n";
    exit;
}

try {
    $spreadsheet = IOFactory::load($path);
    $worksheet = $spreadsheet->getActiveSheet();

    foreach ($worksheet->getRowIterator() as $index => $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);

        $cells = [];
        foreach ($cellIterator as $cell) {
            $cells[] = $cell->getValue();
        }

        if ($index == 1) {
            $headers = array_map(function ($h) {
                return strtolower(trim((string) $h));
            }, $cells);

            echo "Headers found (" . count($headers) . "):\n";
            print_r($headers);

            $idxUsaha = array_search('usaha', $headers);
            echo "Index of 'usaha': " . ($idxUsaha === false ? 'FALSE' : $idxUsaha) . "\n";

        } elseif ($index == 2) {
            echo "Row 2 data:\n";
            print_r($cells);

            $val = $cells[22] ?? 'MISSING';
            echo "Value at index 22 (usaha fallback): " . $val . "\n";
            exit; // Just check first row
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
