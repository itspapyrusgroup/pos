<?php

namespace App\Mail;

use App\Services\ShiftKasirEmailReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TutupKasirLaporanHarianMail extends Mailable
{
    use Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(
        public array $report,
        public ?string $pdfFilename = null
    ) {
        // Guard payload safety for queued serialization.
        $this->report = $this->sanitizeUtf8Recursive($this->report);
        $this->pdfFilename = $this->sanitizeUtf8($this->pdfFilename ?? 'laporan-tutup-kasir.pdf');
    }

    public function build(): self
    {
        $subjectDate = (string) ($this->report['report_date_label'] ?? '-');
        $cabangName = $this->sanitizeUtf8((string) ($this->report['cabang_name'] ?? 'Cabang'));

        $mail = $this
            ->subject('[Papyrus POS] Laporan Tutup Kasir ' . $cabangName . ' - ' . $subjectDate)
            ->view('emails.tutup-kasir-harian', [
                'report' => $this->report,
            ]);

        // Generate and attach PDF in build() method
        try {
            $pdf = Pdf::loadView('pdf.tutup-kasir-laporan', ['report' => $this->report]);
            $pdf->setPaper('A4', 'landscape');
            $pdf->setOptions([
                'isRemoteEnabled' => false,
                'isPhpEnabled' => false,
            ]);
            $pdfBinary = $pdf->output();
            $filename = $this->pdfFilename ?: 'laporan-tutup-kasir.pdf';

            if (strlen($pdfBinary) > 100) {
                $mail->attachData($pdfBinary, $filename, ['mime' => 'application/pdf']);
            }
        } catch (\Throwable $e) {
            // If PDF fails, continue without attachment
            report($e);
        }

        return $mail;
    }

    private function sanitizeUtf8(string $value): string
    {
        if (!mb_check_encoding($value, 'UTF-8')) {
            $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
            if ($converted !== false) {
                $value = $converted;
            } else {
                $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            }
        }
        return trim($value);
    }

    private function sanitizeUtf8Recursive(mixed $value): mixed
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $safeKey = is_string($key) ? $this->sanitizeUtf8($key) : $key;
                $result[$safeKey] = $this->sanitizeUtf8Recursive($item);
            }
            return $result;
        }

        if (is_object($value)) {
            return $this->sanitizeUtf8Recursive((array) $value);
        }

        if (is_string($value)) {
            return $this->sanitizeUtf8($value);
        }

        return $value;
    }
}
