<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class XlsxExportService
{
    public function download(string $filename, array $headers, iterable $rows, string $sheetTitle = 'Laporan'): StreamedResponse
    {
        $safeFile = trim($filename) !== '' ? $filename : 'laporan';
        if (!str_ends_with(strtolower($safeFile), '.xlsx')) {
            $safeFile .= '.xlsx';
        }

        return response()->streamDownload(function () use ($headers, $rows, $sheetTitle) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle(substr(preg_replace('/[\\\\\\/*?:\\[\\]]/', '', $sheetTitle) ?: 'Laporan', 0, 31));

            $rowNum = 1;
            $colNum = 1;
            foreach ($headers as $header) {
                $cell = Coordinate::stringFromColumnIndex($colNum) . $rowNum;
                $sheet->setCellValue($cell, (string) $header);
                $colNum++;
            }

            $lastHeaderCol = Coordinate::stringFromColumnIndex(max(count($headers), 1));
            $sheet->getStyle("A1:{$lastHeaderCol}1")->getFont()->setBold(true);

            foreach ($rows as $row) {
                $rowNum++;
                $colNum = 1;
                foreach ($row as $value) {
                    $cell = Coordinate::stringFromColumnIndex($colNum) . $rowNum;
                    $sheet->setCellValue($cell, $value);
                    $colNum++;
                }
            }

            for ($i = 1; $i <= max(count($headers), 1); $i++) {
                $col = Coordinate::stringFromColumnIndex($i);
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }, $safeFile, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
