<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Sync\SyncReceiveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncInboundController extends Controller
{
    public function push(Request $request, SyncReceiveService $syncReceiveService): JsonResponse
    {
        if (!(bool) config('sync.enabled')) {
            return response()->json(['message' => 'Sync disabled'], 503);
        }

        $role = (string) config('sync.role');
        if (!in_array($role, ['receiver', 'both'], true)) {
            return response()->json(['message' => 'Node ini bukan receiver'], 403);
        }

        $expectedKey = (string) config('sync.api_key');
        $providedKey = (string) $request->header('X-Sync-Key', '');
        if ($expectedKey === '' || !hash_equals($expectedKey, $providedKey)) {
            return response()->json(['message' => 'Unauthorized sync key'], 401);
        }

        $validated = $request->validate([
            'trigger' => ['nullable', 'string', 'max:20'],
            'sent_at' => ['nullable', 'date'],
            'datasets' => ['required', 'array'],
            'datasets.*.name' => ['required', 'string'],
            'datasets.*.rows' => ['required', 'array'],
        ]);

        $summary = $syncReceiveService->ingest($validated['datasets'], 'sync.datasets');

        return response()->json([
            'message' => 'Sync inbound applied',
            'applied_rows' => (int) ($summary['applied_rows'] ?? 0),
            'datasets' => (array) ($summary['datasets'] ?? []),
        ]);
    }
}
