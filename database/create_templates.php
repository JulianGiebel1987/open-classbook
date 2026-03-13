<?php

/**
 * Erstellt die Excel-Import-Vorlagen
 * Verwendung: php database/create_templates.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Lehrer-Import-Vorlage
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Lehrer-Import');

$headers = ['Vorname', 'Nachname', 'Kuerzel', 'E-Mail', 'Faecher', 'Klassen'];
foreach ($headers as $i => $header) {
    $col = chr(65 + $i); // A, B, C, ...
    $sheet->setCellValue($col . '1', $header);
    $sheet->getColumnDimension($col)->setAutoSize(true);
    $sheet->getStyle($col . '1')->getFont()->setBold(true);
}

// Beispielzeile
$sheet->setCellValue('A2', 'Max');
$sheet->setCellValue('B2', 'Mustermann');
$sheet->setCellValue('C2', 'MU');
$sheet->setCellValue('D2', 'max.mustermann@schule.de');
$sheet->setCellValue('E2', 'Deutsch, Mathematik');
$sheet->setCellValue('F2', '5a, 6b');

$writer = new Xlsx($spreadsheet);
$writer->save(__DIR__ . '/../templates/Lehrer-Import.xlsx');
echo "Lehrer-Import.xlsx erstellt.\n";

// Schueler-Import-Vorlage
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Schueler-Import');

$headers = ['Vorname', 'Nachname', 'Klasse', 'Geburtsdatum', 'Erziehungsberechtigten-E-Mail'];
foreach ($headers as $i => $header) {
    $col = chr(65 + $i);
    $sheet->setCellValue($col . '1', $header);
    $sheet->getColumnDimension($col)->setAutoSize(true);
    $sheet->getStyle($col . '1')->getFont()->setBold(true);
}

// Beispielzeile
$sheet->setCellValue('A2', 'Anna');
$sheet->setCellValue('B2', 'Musterfrau');
$sheet->setCellValue('C2', '5a');
$sheet->setCellValue('D2', '15.03.2014');
$sheet->setCellValue('E2', 'eltern@beispiel.de');

$writer = new Xlsx($spreadsheet);
$writer->save(__DIR__ . '/../templates/Schueler-Import.xlsx');
echo "Schueler-Import.xlsx erstellt.\n";
