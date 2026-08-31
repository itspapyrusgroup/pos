<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DailyFinalHarianReportMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(public array $report)
    {
        $this->onQueue('emails');
    }

    public function build(): self
    {
        $subjectDate = (string) ($this->report['report_date_label'] ?? '-');
        $cabangName = (string) ($this->report['cabang_name'] ?? 'Cabang');

        $mail = $this
            ->subject('[Papyrus POS] Laporan Final Harian - ' . $cabangName . ' - ' . $subjectDate)
            ->view('emails.laporan-final-harian', [
                'report' => $this->report,
            ]);

        $binary = $this->buildExcelBinary();
        if ($binary !== '') {
            $mail->attachData(
                $binary,
                $this->attachmentFilename(),
                ['mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            );
        }

        return $mail;
    }

    private function attachmentFilename(): string
    {
        $safeCabang = preg_replace('/[^A-Za-z0-9\\-_]/', '-', (string) ($this->report['cabang_name'] ?? 'cabang')) ?: 'cabang';
        $reportDate = (string) ($this->report['report_date'] ?? now()->toDateString());
        return 'laporan-final-harian-' . $safeCabang . '-' . str_replace('-', '', $reportDate) . '.xlsx';
    }

    private function buildExcelBinary(): string
    {
        $spreadsheet = new Spreadsheet();

        $this->fillSummarySheet($spreadsheet);
        $this->fillPaketSheet($spreadsheet);
        $this->fillPaymentSheet($spreadsheet);
        $this->fillDiskonSheet($spreadsheet);
        $this->fillKasirSheet($spreadsheet);

        ob_start();
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        $binary = (string) ob_get_clean();
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $binary;
    }

    private function fillSummarySheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ringkasan');

        $rows = [
            ['Laporan Final Harian', ''],
            ['Cabang', (string) ($this->report['cabang_name'] ?? '-')],
            ['Tanggal', (string) ($this->report['report_date_label'] ?? '-')],
            ['Generated At', (string) ($this->report['generated_at'] ?? '-')],
            ['Timezone', (string) ($this->report['timezone'] ?? '-')],
            ['', ''],
            ['Jumlah Transaksi', (float) ($this->report['summary']['jumlah_transaksi'] ?? 0)],
            ['Total Item Terjual', (float) ($this->report['summary']['total_item_terjual'] ?? 0)],
            ['Total Paket Terjual', (float) ($this->report['summary']['total_paket_terjual'] ?? 0)],
            ['Kas Masuk Kotor', (float) ($this->report['summary']['total_pembayaran_kotor'] ?? 0)],
            ['Void/Refund Kas', (float) ($this->report['summary']['total_pembayaran_void'] ?? 0)],
            ['Pendapatan Bersih', (float) ($this->report['summary']['pendapatan_bersih'] ?? 0)],
            ['Total Void Order', (float) ($this->report['summary']['total_void_order'] ?? 0)],
            ['Total Diskon', (float) ($this->report['summary']['total_diskon'] ?? 0)],
            ['Total Sisa Piutang', (float) ($this->report['summary']['total_sisa'] ?? 0)],
            ['Shift Closed', (float) ($this->report['summary']['shift_closed'] ?? 0)],
        ];

        $this->fillRows($sheet, $rows);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A7:A16')->getFont()->setBold(true);
        foreach ([10, 11, 12, 13, 14, 15] as $moneyRow) {
            $sheet->getStyle('B' . $moneyRow)->getNumberFormat()->setFormatCode('#,##0');
        }
    }

    private function fillPaketSheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Paket Terjual');

        $headers = ['Kode', 'Nama Paket', 'Qty', 'Omzet Bruto', 'Diskon', 'Omzet Neto'];
        $rows = array_map(function (array $row) {
            return [
                $row['kode'] ?? '-',
                $row['nama'] ?? '-',
                (float) ($row['qty'] ?? 0),
                (float) ($row['bruto'] ?? 0),
                (float) ($row['diskon'] ?? 0),
                (float) ($row['neto'] ?? 0),
            ];
        }, (array) ($this->report['paket_summary'] ?? []));

        $this->fillTabular($sheet, $headers, $rows);
        foreach (['D', 'E', 'F'] as $col) {
            $sheet->getStyle($col . '2:' . $col . max(count($rows) + 1, 2))->getNumberFormat()->setFormatCode('#,##0');
        }
    }

    private function fillPaymentSheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Pembayaran');

        $headers = ['Kode', 'Metode', 'Jumlah Transaksi', 'Kas Masuk Kotor', 'Void/Refund', 'Kas Bersih'];
        $rows = array_map(function (array $row) {
            return [
                $row['kode'] ?? '-',
                $row['nama'] ?? '-',
                (float) ($row['jumlah_transaksi'] ?? 0),
                (float) ($row['total_kotor'] ?? 0),
                (float) ($row['total_void'] ?? 0),
                (float) ($row['total'] ?? 0),
            ];
        }, (array) ($this->report['payment_by_method'] ?? []));

        $this->fillTabular($sheet, $headers, $rows);
        foreach (['D', 'E', 'F'] as $col) {
            $sheet->getStyle($col . '2:' . $col . max(count($rows) + 1, 2))->getNumberFormat()->setFormatCode('#,##0');
        }
    }

    private function fillDiskonSheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Diskon');

        $rows = [
            ['Diskon Item', (float) ($this->report['discount_summary']['diskon_item'] ?? 0)],
            ['Diskon Order', (float) ($this->report['discount_summary']['diskon_order'] ?? 0)],
            ['Total Diskon', (float) ($this->report['discount_summary']['total_diskon'] ?? 0)],
        ];

        $this->fillRows($sheet, $rows);
        $sheet->getStyle('A1:A3')->getFont()->setBold(true);
        $sheet->getStyle('B1:B3')->getNumberFormat()->setFormatCode('#,##0');
    }

    private function fillKasirSheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Per Kasir');

        $headers = ['Tanggal', 'Kasir', 'No KO', 'Customer', 'Paket/Item', 'Metode Pembayaran', 'Total Kas Bersih', 'Diskon'];

        $rows = [];
        foreach ((array) ($this->report['kasir_grouped'] ?? []) as $group) {
            $rows[] = ['', 'Kasir: ' . ($group['kasir'] ?? '-'), '', '', '', '', '', ''];
            $rows[] = $headers;

            foreach ((array) ($group['rows'] ?? []) as $row) {
                $rows[] = [
                    $row['tanggal'] ?? '-',
                    $row['kasir'] ?? '-',
                    $row['no_ko'] ?? '-',
                    $row['customer'] ?? '-',
                    $row['item_ringkas'] ?? '-',
                    $row['metode_pembayaran'] ?? '-',
                    (float) ($row['total_bayar_masuk'] ?? 0),
                    (float) ($row['total_diskon'] ?? 0),
                ];
            }

            $rows[] = [
                '', '', '', '', '', 'Subtotal ' . ($group['kasir'] ?? '-'),
                (float) (($group['subtotal']['total_bayar_masuk'] ?? 0)),
                (float) (($group['subtotal']['total_diskon'] ?? 0)),
            ];
            $rows[] = ['', '', '', '', '', '', '', ''];
        }

        $rows[] = ['', '', '', '', '', 'Grand Total Kas Bersih', (float) (($this->report['kasir_grand_total']['total_bayar_masuk'] ?? 0)), (float) (($this->report['kasir_grand_total']['total_diskon'] ?? 0))];
        $rows[] = ['', '', '', '', '', 'Grand Total Transaksi', (float) (($this->report['kasir_grand_total']['jumlah_transaksi'] ?? 0)), ''];

        $this->fillTabular($sheet, $headers, $rows);

        $lastRow = count($rows) + 1;
        foreach (['G', 'H'] as $col) {
            $sheet->getStyle($col . '2:' . $col . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
        }
        $sheet->getStyle('F' . ($lastRow - 1) . ':H' . $lastRow)->getFont()->setBold(true);
    }

    private function fillRows(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $rows): void
    {
        $rowNum = 1;
        foreach ($rows as $row) {
            $sheet->setCellValue('A' . $rowNum, $row[0] ?? '');
            $sheet->setCellValue('B' . $rowNum, $row[1] ?? '');
            $rowNum++;
        }

        for ($i = 1; $i <= 2; $i++) {
            $col = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function fillTabular(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $headers, array $rows): void
    {
        $rowNum = 1;
        foreach ($headers as $idx => $header) {
            $col = Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->setCellValue($col . $rowNum, $header);
        }

        foreach ($rows as $row) {
            $rowNum++;
            foreach ($row as $idx => $value) {
                $col = Coordinate::stringFromColumnIndex($idx + 1);
                $sheet->setCellValue($col . $rowNum, $value);
            }
        }

        $lastCol = Coordinate::stringFromColumnIndex(max(count($headers), 1));
        $sheet->getStyle('A1:' . $lastCol . '1')->getFont()->setBold(true);
        for ($i = 1; $i <= count($headers); $i++) {
            $col = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}
