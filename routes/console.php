<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\DailyFinalEmailReportService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sync:push-cloud')
    ->everyMinute()
    ->withoutOverlapping();

$pullEvery = max(1, (int) config('sync.pull_master_interval_minutes', 5));
if ((bool) config('sync.pull_master_auto')) {
    $pullCabangId = (int) config('sync.pull_master_cabang_id', 0);
    $pullMode = strtolower(trim((string) config('sync.pull_master_mode', 'incremental')));
    $cmd = 'sync:bootstrap-pull'
        . ($pullCabangId > 0 ? (' --cabang_id=' . $pullCabangId) : '')
        . ' --mode=' . ($pullMode === 'bootstrap' ? 'bootstrap' : 'incremental');

    if ($pullEvery <= 1) {
        Schedule::command($cmd)->everyMinute()->withoutOverlapping();
    } elseif ($pullEvery <= 5) {
        Schedule::command($cmd)->everyFiveMinutes()->withoutOverlapping();
    } elseif ($pullEvery <= 10) {
        Schedule::command($cmd)->everyTenMinutes()->withoutOverlapping();
    } elseif ($pullEvery <= 15) {
        Schedule::command($cmd)->everyFifteenMinutes()->withoutOverlapping();
    } else {
        Schedule::command($cmd)->everyThirtyMinutes()->withoutOverlapping();
    }
}

Artisan::command('laporan:send-daily-final {--date=} {--cabang_id=*}', function () {
    $date = (string) ($this->option('date') ?: now()->toDateString());
    $cabangIds = (array) $this->option('cabang_id');

    $result = app(DailyFinalEmailReportService::class)->sendForDate($date, $cabangIds);
    $this->info(
        'Laporan final harian queued. '
        . 'Date=' . $result['date']
        . ', total_cabang=' . $result['total_cabang']
        . ', sent=' . $result['sent']
        . ', skipped=' . $result['skipped']
    );
})->purpose('Kirim laporan final harian per cabang via email');

Schedule::command('laporan:send-daily-final')
    ->dailyAt('23:59')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();
