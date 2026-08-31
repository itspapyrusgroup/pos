<?php

namespace App\Http\Controllers;

use App\Models\SyncPushCursor;
use App\Services\Sync\SyncBootstrapPullService;
use App\Services\Sync\SyncPushService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SyncControlController extends Controller
{
    private function ensureNotCloudCenter(): void
    {
        abort_if((string) config('sync.app_mode') === 'cloud_center', 404);
    }

    public function index(): View
    {
        $this->ensureNotCloudCenter();

        $target = (string) config('sync.target_name', 'cloud');
        $datasets = (array) config('sync.datasets', []);
        $cursors = SyncPushCursor::query()
            ->where('target', $target)
            ->orderBy('dataset')
            ->get()
            ->keyBy('dataset');

        return view('sync-control', [
            'syncEnabled' => (bool) config('sync.enabled'),
            'syncRole' => (string) config('sync.role'),
            'appMode' => (string) config('sync.app_mode'),
            'pushUrl' => (string) config('sync.cloud_push_url'),
            'bootstrapUrl' => (string) config('sync.cloud_bootstrap_url'),
            'target' => $target,
            'datasets' => $datasets,
            'cursors' => $cursors,
            'cabangTersedia' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'activeCabangId' => (int) ($this->activeCabangId() ?? 0),
            'lastPushResult' => Cache::get('sync:last_push_result'),
            'lastBootstrapResult' => Cache::get('sync:last_bootstrap_result'),
            'promoStats' => [
                'voucher_total' => (int) DB::table('voucher_promosi')->count(),
                'voucher_map_total' => DB::getSchemaBuilder()->hasTable('voucher_promosi_cabang')
                    ? (int) DB::table('voucher_promosi_cabang')->count()
                    : 0,
                'diskon_total' => (int) DB::table('diskon_otomatis')->count(),
                'diskon_map_total' => DB::getSchemaBuilder()->hasTable('diskon_otomatis_cabang')
                    ? (int) DB::table('diskon_otomatis_cabang')->count()
                    : 0,
            ],
        ]);
    }

    public function manualPush(SyncPushService $syncPushService): RedirectResponse
    {
        $this->ensureNotCloudCenter();

        $result = $syncPushService->pushToCloud('manual');
        if (!($result['ok'] ?? false)) {
            return back()->with('sync_error', (string) ($result['message'] ?? 'Sinkronisasi gagal.'));
        }

        $message = sprintf(
            'Sinkronisasi sukses. Rows terkirim: %d. Dataset: %s',
            (int) ($result['sent_rows'] ?? 0),
            implode(', ', (array) ($result['datasets'] ?? []))
        );

        return back()->with('sync_success', $message);
    }

    public function manualBootstrap(Request $request, SyncBootstrapPullService $bootstrapPullService): RedirectResponse
    {
        $this->ensureNotCloudCenter();

        $data = $request->validate([
            'cabang_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $cabangId = isset($data['cabang_id']) && (int) $data['cabang_id'] > 0
            ? (int) $data['cabang_id']
            : (int) ($this->activeCabangId() ?? 0);
        $this->ensureCabangAccessible($cabangId);

        $result = $bootstrapPullService->pullMasterFromCloud($cabangId, 'manual', 'bootstrap');
        if (!($result['ok'] ?? false)) {
            return back()->with('sync_error', (string) ($result['message'] ?? 'Bootstrap gagal.'));
        }

        $message = sprintf(
            'Bootstrap sukses. Applied rows: %d. Detail: %s',
            (int) ($result['applied_rows'] ?? 0),
            collect((array) ($result['dataset_rows'] ?? []))
                ->map(fn ($count, $name) => $name . '=' . $count)
                ->implode(', ')
        );

        return back()->with('sync_success', $message);
    }
}
