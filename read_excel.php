<?php
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

$reader = new Xlsx();
$spreadsheet = $reader->load('GAIN & METRO BRANCH CONTACT LIST 2024.xlsx');
$worksheet = $spreadsheet->getActiveSheet();

$data = [];
foreach ($worksheet->getRowIterator() as $row) {
    $rowData = [];
    $cellIterator = $row->getCellIterator();
    $cellIterator->setIterateOnlyExistingCells(false);
    foreach ($cellIterator as $cell) {
        $rowData[] = $cell->getValue();
    }
    $data[] = $rowData;
}

echo json_encode($data, JSON_PRETTY_PRINT);
